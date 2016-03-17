<?php

class Aoe_Static_Model_AsyncCacheObserver
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function aoeasynccacheProcessqueuePostprocessjobcollection(Varien_Event_Observer $observer)
    {
        /** @var $jobCollection Aoe_AsyncCache_Model_JobCollection */
        $jobCollection = $observer->getData('jobCollection');
        if (!$jobCollection) {
            return;
        }
        foreach ($jobCollection as $job) {
            /** @var $job Aoe_AsyncCache_Model_Job */
            /** @todo name */
            if (!$job->getIsProcessed() && ($job->getMode() == Aoe_Static_Helper_Data::MODE_PURGEVARNISHURL)) {
                $startTime = time();
                $errors = Mage::helper('aoestatic')->purge($job->getTags(), false);
                $job->setDuration(time() - $startTime);
                $job->setIsProcessed(true);

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        Mage::log($error);
                    }
                }

                Mage::helper('aoestatic')->log(sprintf('[ASYNCCACHE URL] MODE: %s, DURATION: %s sec, TAGS: %s',
                    $job->getMode(),
                    $job->getDuration(),
                    implode(', ', $job->getTags())
                ));
            } else if (!$job->getIsProcessed() && ($job->getMode() == Aoe_Static_Helper_Data::MODE_PURGEVARNISHTAG)) {
                $startTime = time();
                $errors = Mage::helper('aoestatic')->purgeTags($job->getTags(), 0, false);
                $job->setDuration(time() - $startTime);
                $job->setIsProcessed(true);

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        Mage::log($error);
                    }
                }

                Mage::helper('aoestatic')->log(sprintf('[ASYNCCACHE TAG] MODE: %s, DURATION: %s sec, TAGS: %s',
                    $job->getMode(),
                    $job->getDuration(),
                    implode(', ', $job->getTags())
                ));
            }
        }
    }
}
