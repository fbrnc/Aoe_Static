backend default {
    .host = "127.0.0.1";
    .port = "80";
    .first_byte_timeout = 300s;
}

acl cache_acl {
    "127.0.0.1";
    # insert additional ip's here
}

sub vcl_recv {
    # Restricted processing
    if (client.ip ~ cache_acl) {
        # BAN requests
        if (req.request == "BAN") {
            if(req.http.X-Tags) {
                ban("obj.http.X-Tags ~ " + req.http.X-Tags);
            }
            if(req.http.X-Url) {
                ban("obj.http.X-Url ~ " + req.http.X-Url);
            }
            error 200 "Banned";
        }

        # Convert to a PURGE
        if (req.http.Cache-Control == "no-cache") {
            remove req.http.Cache-Control;
            set req.request = "PURGE";
        }

        // PURGE requests are handled in hit/miss
        if (req.request == "PURGE") {
            return(lookup);
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
            remove req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            remove req.http.Accept-Encoding;
        }
    }

    # Varnish handles GET/HEAD and backend handles PUT/POST/DELETE
    if (req.request != "GET" && req.request != "HEAD") {
        if (req.request != "PUT" && req.request != "POST" && req.request != "DELETE") {
            error 405 "Method Not Allowed";
        }
        return (pass);
    }

    # Remove cookie for known-static file extensions
    if (req.url ~ "^[^?]*\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)$") {
        remove req.http.Cookie;
    }

    # Backend handles requests with an Authorization header
    if (req.http.Authorization) {
        return (pass);
    }

    # This is needed as we can return cached results even when cookies are in the request
    return (lookup);
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
    return (hash);
}

sub vcl_hit {
    # PURGE requests
    if (req.request == "PURGE") {
        purge;
        error 200 "Purged";
    }
}

sub vcl_miss {
    # PURGE requests
    if (req.request == "PURGE") {
        purge;
        error 200 "Purged";
    }
}

sub vcl_fetch {
    # set minimum timeouts to auto-discard stored objects
    set beresp.grace = 600s;

    # Add URL used for PURGE
    set beresp.http.X-Url = req.url;

    if (beresp.http.X-Aoestatic == "cache") {
        # Cacheable object as indicated by backend response
        remove beresp.http.Set-Cookie;
        remove beresp.http.Age;
        remove beresp.http.Pragma;
        set beresp.http.Cache-Control = "public";
        set beresp.grace = 1h;
        set beresp.ttl = 1d;
        set beresp.http.X-Aoestatic-Fetch = "Removed cookie in vcl_fetch";
    } else if (req.url ~ "^[^?]+\.(css|js|htc|xml|txt|swf|flv|pdf|gif|jpe?g|png|ico)(\?.*)?$") {
        # Known-static file extensions
        remove beresp.http.Set-Cookie;
        remove beresp.http.Age;
        remove beresp.http.Pragma;
        set beresp.http.Cache-Control = "public";
        set beresp.grace = 1h;
        set beresp.ttl = 1d;
        set beresp.http.X-Tags = "STATIC";
        set beresp.http.X-Aoestatic-Fetch = "Removed cookie in vcl_fetch";
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
    } else {
        # Remove internal headers
        remove resp.http.Via;
        remove resp.http.Server;
        remove resp.http.X-Varnish;
        remove resp.http.X-Url;
        remove resp.http.X-Tags;
        remove resp.http.X-Aoestatic;
        remove resp.http.X-Aoestatic-Debug;
        remove resp.http.X-Aoestatic-Fetch;
        remove resp.http.X-Aoestatic-Pass;
        remove resp.http.X-Aoestatic-Action;
        remove resp.http.X-Aoestatic-Lifetime;
    }
}

sub vcl_pipe {
    # http://www.varnish-cache.org/ticket/451
    # This forces every pipe request to be the first one.
    set bereq.http.connection = "close";
}
