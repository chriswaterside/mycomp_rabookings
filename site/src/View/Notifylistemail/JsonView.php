<?php

/*
 * Email all on waiting/notification list that places available
 *      parameters
 *         POST data
 *             id - id of event
 *             event - json version of walk/event Ramblers-webs format
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=notifylistemail&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Notifylistemail;

use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;
use Joomla\CMS\Log\Log;

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

            $to = $ebRecord->wlc->getArray();
            $replyTo = helper::getEventContact($ebRecord);
            $title = helper::getEmailTitle('', $ew);
            $content = helper::getEmailTemplate('notifylistemail.html', $ew);
            $content = helper::updateEmailforBooking($content, $ebRecord);
            helper::sendEmailsToUser($to, null, $replyTo, $title, $content);
            // $feedback[] = '<h3>Emails have been sent</h3>';
            $record = new \stdClass();
            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            Log::add("Exception thrown in $file on line $line: [Code $code] $message", Log::ERROR, 'com_ra_eventbooking');
            echo new JsonResponse($e);
        }
    }
}
