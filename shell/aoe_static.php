<?php

require_once 'abstract.php';

class Aoe_Static_Shell extends Mage_Shell_Abstract
{

    public function purgeAllAction()
    {
        $errors = Mage::helper('aoestatic')->purgeAll();
        var_dump($errors);
    }

    public function purgeUrlAction()
    {
        $url = $this->getArg('url');
        if (!$url) {
            $this->usageHelp();
            exit(1);
        }
        echo "Purging cache for '$url'";
        $helper = Mage::helper('aoestatic'); /* @var $helper Aoe_Static_Helper_Data */
        $errors = $helper->purge(array($url), false);
        var_dump($errors);
    }

    public function purgeUrlsActionHelp()
    {
        return ' -url <url/regex>';
    }

    public function purgeTagAction()
    {
        $tag = $this->getArg('tag');
        if (!$tag) {
            $this->usageHelp();
            exit(1);
        }
        echo "Purging cache for tag '$tag'";
        $helper = Mage::helper('aoestatic'); /* @var $helper Aoe_Static_Helper_Data */
        $errors = $helper->purgeTags(array($tag));
        var_dump($errors);
    }

    public function purgeTagActionHelp()
    {
        return ' -tag <tag>';
    }

    /**
     * Run script
     */
    public function run()
    {
        $action = $this->getArg('action');
        if (empty($action)) {
            echo $this->usageHelp();
        } else {
            $actionMethodName = $action . 'Action';
            if (method_exists($this, $actionMethodName)) {
                $this->$actionMethodName();
            } else {
                echo "Action $action not found!\n";
                echo $this->usageHelp();
                exit(1);
            }
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp()
    {
        $help = 'Available actions: ' . "\n";
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (substr($method, -6) == 'Action') {
                $help .= '    -action ' . substr($method, 0, -6);
                $helpMethod = $method . 'Help';
                if (method_exists($this, $helpMethod)) {
                    $help .= $this->$helpMethod();
                }
                $help .= "\n";
            }
        }
        return $help;
    }

}

$shell = new Aoe_Static_Shell();
$shell->run();
