<?php

/*
 * Submit a booking
 *      Parameters
 * 
 *        item - the id of the event 
 *        attendees - no of attendees
 *      if user is not logged on and guest bookings are allowed
 *        name - name of person making booking
 *        email - email address of person making booking
 *        
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=submitbooking&format=json
 * 
 * EW     an RA event or walk in ramblers library format
 * ESC    a collection of booking records , EVB
 * EVB    a booking record for an event, an object
 * NBI    a new booking information for one user
 * BLC    a collection of bookings, collection of BLI
 * BLI    the user information booking for a user
 * WLC    a collection of waiting records, collection of WLI
 * WLI    the user information about someone on waiting list
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Submitbooking;

use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;
use Joomla\CMS\Component\ComponentHelper;

// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {
        // Input user details and number of attendees
        // check if user has existing booking
        // remove from waiting list if necessary 
        // update booking list and waiting list
        // send email to booker and contact

        try {
            // get global settings

            $feedback = [];
            $componentParams = ComponentHelper::getParams('com_ra_eventbooking');
            $guest = $componentParams->get('guest', false) === "1";
            $maxattendees = intval($componentParams->get('maxattendees', 0));
            $maxguestattendees = intval($componentParams->get('maxguestattendees', 1));

            $data = helper::getPostedData();
            $ewid = $data->ewid;
            $ew = $data->ew;
            $attach = new \stdClass();
            $attach->data = $data->ics;
            $attach->type = 'string';
            $attach->encoding = 'base64';
            $attach->filename = 'walk.ics';
            $attach->mimeType = 'text/calendar';
            $bookingData = $data->bookingData;
            if ($bookingData->id > 0) {
                $juser = \JFactory::getUser($bookingData->id);
                $bookingData->email = $juser->email;
                $bookingData->name = $juser->name;
            }
            self::checkInput($guest, $maxattendees, $maxguestattendees, $bookingData);
            $id = $bookingData->id;
            $name = $bookingData->name;
            $email = $bookingData->email;
            $attendees = $bookingData->attendees;
            $paid = $bookingData->paid;
            $newBooking = helper::getNewBooking($id, $name, $email, $attendees, $paid, "Internal");

            // retrieve current booking data
            $ebRecord = helper::getEVBrecord($ewid, "Internal");
            helper::checkBooking($ebRecord, $newBooking);
            helper::updateBooking($ebRecord, $newBooking);
            helper::updateDBField($ewid, 'booking_data', json_encode($ebRecord->blc), 'string');
            if ($newBooking->noAttendees() > 0) {
                $feedback[] = '<h3>You have been booked on this event</h3>';
                $emailTemplate = 'newbooking.html';
            } else {
                $feedback[] = '<h3>Your booking for this event has been removed/cancelled</h3>';
                $emailTemplate = 'removebooking.html';
            }

            $isWaiting = $ebRecord->wlc->isWaiting($email);
            if ($isWaiting !== null) {
                $ebRecord->wlc->remove($email);
                helper::updateDBField($ewid, 'waiting_data', json_encode($ebRecord->wlc), 'string');
                $feedback[] = '<h3>We have removed you from the waiting list</h3>';
            }

            // send email confirmation
            $to[] = $newBooking;
            $action = 'BOOKING';
            if ($attendees === 0) {
                $action = 'CANCEL';
            }
            $replyTo = helper::getEventContact($ebRecord);
            $copyTo = helper::getEventContact($ebRecord);
            $title = helper::getEmailTitle($action, $ew);
            $content = helper::getEmailTemplate($emailTemplate, $ew);
            helper::sendEmailsToUser($to, $copyTo, $replyTo, $title, $content, $attach);

            // return status of booking
            $record = new \stdClass();
            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }

    private static function checkInput($guest, $maxattendees, $maxguestattendees, $bookingData) {
        $juser = \JFactory::getUser($bookingData->id);
        $canEdit = helper::canEdit();
        if (!$canEdit) {
            if ($juser->id !== $bookingData->id) {
                throw new \RuntimeException('Invalid user details: you may have been logged out of web site');
            }
        }
        if (!$guest && $bookingData->id === 0) {
            throw new \RuntimeException('No guest bookings allowed');
        }
        if ($bookingData->id > 0) {
            if ($bookingData->attendees > $maxattendees) {
                throw new \RuntimeException('Booking exceeds number allowed');
            }
        } else {
            if (strlen($bookingData->name) === 0) {
                throw new \RuntimeException('Invalid user details: name');
            }
            if (strlen($bookingData->email) === 0) {
                throw new \RuntimeException('Invalid user details: email');
            }
            if ($bookingData->attendees > $maxguestattendees) {
                throw new \RuntimeException('Booking exceeds number allowed');
            }
        }
    }
}
