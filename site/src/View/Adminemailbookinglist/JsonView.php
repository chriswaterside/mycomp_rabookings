<?php

/*
 * Admin email booking list
 *      parameters
 *         POST data
 *             id - id of event
 *             event - json version of walk/event Ramblers-webs format
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=adminemailbookinglist&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Adminemailbookinglist;

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

            $ew = json_decode($data->ew);
            $ewid = $data->ewid;
            $user = $data->user;
            $ebRecord = helper::getEVBrecord($ewid, "Internal");
            $bookinglist = $ebRecord->blc->getBookingTable($ebRecord->payment_required);

            $juser = Factory::getUser();
            $to = [helper::getSendTo($juser->name, $juser->email)];

            $replyTo = null;
            $copy = helper::eventContactEmail($ebRecord);

            if ($copy->email === $juser->email) {
                $copy = null;
            }
            $title = helper::getEmailTitle('BOOKING LIST', $ew);
            $content = helper::getEmailContent('emailbookinglist.html', $ew);
            $content = str_replace("{bookinglist}", $bookinglist, $content);
            helper::sendEmails($to, $copy, $replyTo, $title, $content);

            $feedback[] = '<h3>Email has been sent</h3>';
            $record = new \stdClass();
            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
