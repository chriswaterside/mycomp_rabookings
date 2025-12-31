<?php

/*
 * Eventchanged
 *      parameters
 *         POST data
 *             id - id of event
 *             event - json version of walk/event Ramblers-webs format
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=Eventchanged&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Eventchanged;

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
            $attach = new \stdClass();
            $attach->data = $data->ics;
            $attach->type = 'string';
            $attach->encoding = 'base64';
            $attach->filename = 'walk.ics';
            $attach->mimeType = 'text/calendar';
            $ebRecord = helper::getEVBrecord($ewid, "Internal");
            $ew = json_decode($data->ew);
            $update = new \stdClass();
            $update->dateUpdated = $ew->admin->dateUpdated;
            $update->localPopupUrl = $ew->admin->localPopupUrl;
            $update->date = $ew->basics->walkDate;
            $update->title = $ew->basics->title;
            $update->description = $ew->basics->description;
            helper::updateDBField($ewid, 'event_data', json_encode($update), 'string');

            $to = $ebRecord->blc->getArray();
            $replyTo = helper::eventContactEmail($ebRecord);
            $title = helper::getEmailTitle('Event changed', $ew);
            $content = helper::getEmailContent('eventchanged.html', $ew);
            helper::sendEmails($to, null, $replyTo, $title, $content, $attach);

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
