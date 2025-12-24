<?php

/*
 * Waiting
 *      parameters
 *         POST data
 *             id - id of event
 *             event - json version of walk/event Ramblers-webs format
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=waiting&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Waiting;

use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

// use Joomla\CMS\Component\ComponentHelper;
// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {

        try {
            $feedback = [];
            $juser = \JFactory::getUser();
            $componentParams = ComponentHelper::getParams('com_ra_eventbooking');
            $waitingList = $componentParams->get('waitinglist', false) === "1";
            if (!$waitingList) {
                throw new \RuntimeException('A notification list is not enabled on this web site');
            }
            $data = helper::getPostedData();
            $ewid = $data->ewid;
            $ew = $data->ew;
            $bookingData = $data->bookingData;
            $id = $bookingData->id;
            $name = $bookingData->name;
            $email = $bookingData->email;
            $telephone = $bookingData->telephone;
            if ($id > 0) {
                $email = $juser->email;
                $name = $juser->name;
            }
            $ebRecord = helper::getEVBrecord($ewid, "Internal");
            $item = $ebRecord->wlc->getItemByMd5Email(md5($email));
            if ($item === null) {
                $item = helper::getNewWaiting($id, $name, $email, $telephone, "Internal");
                $ebRecord->wlc->addItem($item);
                $feedback[] = '<h3>We have added you to the list and will notify you when places become available</h3>';
                $emailTemplate = 'waitingadd.html';
            } else {
                $ebRecord->wlc->removeItemByMd5Email(md5($email));
                $feedback[] = '<h3>We have removed you from the list, so you will not receive any further notifications</h3>';
                $emailTemplate = 'waitingdelete.html';
            }
            helper::updateDBField($ewid, 'waiting_list_data', json_encode($ebRecord->wlc), 'string');
            $to = [$item];
            $replyTo = helper::eventContactEmail($ebRecord);
            $copyTo = null;
            $title = helper::getEmailTitle('Notify', $ew);
            $content = helper::getEmailContent($emailTemplate, $ew);
            helper::sendEmails($to, $copyTo, $replyTo, $title, $content);

            $record = new \stdClass();
            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
