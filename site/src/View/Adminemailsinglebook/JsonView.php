<?php

/*
 * Admin email single booker
 *      parameters
 *         POST data
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=adminemailsinglebook&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Adminemailsinglebook;

use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;
use Joomla\CMS\Factory;

// use Joomla\CMS\Component\ComponentHelper;
// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {
        try {
            $feedback = [];
            $data = helper::getPostedData();
            $ewid = $data->ewid;
            $ebRecord = helper::getEVBrecord($ewid, "Internal");
            $ew = json_decode($data->ew);
            $to = [$ebRecord->blc->getItemByMd5Email($data->to->md5Email)];
            if ($to === null) {
                throw new \RuntimeException('Unable to find user record');
            }
                        
            $replyTo = Factory::getUser($data->from->id);
             if ($replyTo === null) {
                throw new \RuntimeException('Unable to find sender');
            }
            $copy = helper::getEventContact($ebRecord);
            $title = helper::getEmailTitle('EMAIL', $ew);
            $content = helper::getEmailTemplate('emailbookers.html', $ew);
            $content = str_replace("{emailContent}", $data->emailContent, $content);
            helper::sendEmailsToUser($to, $copy, $replyTo, $title, $content);

            $feedback[] = '<h3>Email has been sent</h3>';
            $record = new \stdClass();
            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
