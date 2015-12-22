vcl 4.0;

include "backend.vcl";
include "acl.vcl";

import std;

sub vcl_recv {
    # Restricted processing
    # Extracting real client.ip is much easier in Varnish 4 https://www.varnish-cache.org/forum/topic/3064
    if (std.ip(regsub(req.http.X-Forwarded-For, "[, ].*$", ""), client.ip) ~ cache_acl) {
        # BAN requests
        if (req.method == "BAN") {
            if(req.http.X-Tags) {
                ban("obj.http.X-Tags ~ " + req.http.X-Tags);
            }
            if(req.http.X-Url) {
                ban("obj.http.X-Url ~ " + req.http.X-Url);
            }
            return (synth(200, "Banned"));
        }

        # Convert to a PURGE
        if (req.http.Cache-Control == "no-cache") {
            unset req.http.Cache-Control;
            set req.method = "PURGE";
        }

        # PURGE requests are handled in hit/miss
        if (req.method == "PURGE") {
            return (hash);
        }
    }

    # Add or append to X-Forwarded-For header (only on first processing run)
    if (req.restarts == 0) {
        if (req.http.X-Forwarded-For) {
            set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
        }
    }

    # Normalize Accept-Encoding header (http://www.varnish-cache.org/trac/wiki/VCLExampleNormalizeAcceptEncoding)
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|mp4|flv)$") {
            unset req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            unset req.http.Accept-Encoding;
        }
    }

    # Varnish handles GET/HEAD and backend handles PUT/POST/DELETE
    if (req.method != "GET" && req.method != "HEAD") {
        if (req.method != "PUT" && req.method != "POST" && req.method != "DELETE") {
            return (synth(405, "Method Not Allowed"));
        }
        return (pass);
    }

    # Remove cookie for known-static file extensions
    if (req.url ~ "^[^?]*\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)$") {
        unset req.http.Cookie;
    }

    # Backend handles requests with an Authorization header
    if (req.http.Authorization) {
        return (pass);
    }

    # This is needed as we can return cached results even when cookies are in the request
    return (hash);
}

sub vcl_hash {
    hash_data(req.url);
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }
    if (req.http.https) {
        hash_data(req.http.https);
    }
    return (lookup);
}

sub vcl_hit {
    # PURGE requests
    if (req.method == "PURGE") {
        #
        # This is now handled in vcl_recv.
        #
        # purge;
        return (synth(200, "Purged"));
    }
}

sub vcl_miss {
    # PURGE requests
    if (req.method == "PURGE") {
        #
        # This is now handled in vcl_recv.
        #
        # purge;
        return (synth(200, "Purged"));
    }
}

sub vcl_backend_response {
    # set minimum timeouts to auto-discard stored objects
    set beresp.grace = 600s;

    # Add URL used for PURGE
    set beresp.http.X-Url = bereq.url;

    if (beresp.http.X-Aoestatic == "cache") {
        # Cacheable object as indicated by backend response
        unset beresp.http.Set-Cookie;
        unset beresp.http.Age;
        unset beresp.http.Pragma;
        set beresp.http.Cache-Control = "public";
        set beresp.grace = 1h;
        set beresp.ttl = 1d;
        set beresp.http.X-Aoestatic-Fetch = "Removed cookie in vcl_backend_response";
    } else if (bereq.url ~ "^[^?]+\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)(\?.*)?$") {
        # Known-static file extensions
        unset beresp.http.Set-Cookie;
        unset beresp.http.Age;
        unset beresp.http.Pragma;
        set beresp.http.Cache-Control = "public";
        set beresp.grace = 1h;
        set beresp.ttl = 1d;
        set beresp.http.X-Tags = "STATIC";
        set beresp.http.X-Aoestatic-Fetch = "Removed cookie in vcl_backend_response";
}

    if (beresp.status >= 400) {
        # Don't cache negative lookups
        set beresp.http.X-Aoestatic-Pass = "Status greater than 400";
        set beresp.ttl = 0s;
    } else if (beresp.ttl <= 0s) {
        set beresp.http.X-Aoestatic-Pass = "Not cacheable";
        set beresp.ttl = 0s;
    } else if (beresp.http.Set-Cookie) {
        set beresp.http.X-Aoestatic-Pass = "Cookie";
        set beresp.ttl = 0s;
    } else if (!beresp.http.Cache-Control ~ "public") {
        set beresp.http.X-Aoestatic-Pass = "Cache-Control is not public";
        set beresp.ttl = 0s;
    } else if (beresp.http.Pragma ~ "(no-cache|private)") {
        set beresp.http.X-Aoestatic-Pass = "Pragma is no-cache or private";
        set beresp.ttl = 0s;
    }
}

sub vcl_deliver {
    if (resp.http.X-Aoestatic-Debug == "true") {
        # Adding debugging information
        if (obj.hits > 0) {
            set resp.http.X-Cache = "HIT (" + obj.hits + ")";
        } else {
            set resp.http.X-Cache = "MISS";
        }
        set resp.http.Client-ip = client.ip;
    } else {
        # Remove internal headers
        unset resp.http.Via;
        unset resp.http.Server;
        unset resp.http.X-Varnish;
        unset resp.http.X-Url;
        unset resp.http.X-Tags;
        unset resp.http.X-Aoestatic;
        unset resp.http.X-Aoestatic-Debug;
        unset resp.http.X-Aoestatic-Fetch;
        unset resp.http.X-Aoestatic-Pass;
        unset resp.http.X-Aoestatic-Action;
        unset resp.http.X-Aoestatic-Lifetime;
    }
}

sub vcl_pipe {
    # http://www.varnish-cache.org/ticket/451
    # This forces every pipe request to be the first one.
    set bereq.http.connection = "close";
}