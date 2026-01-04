<?php

/*
 * @version    CVS: 1.0.0
 * @package    Com_Ra_eventbooking
 * @author     Chris Vaughan  <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2025 Ruby Tuesday
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * EW     an RA event or walk in ramblers library format
 * ESC    a collection of booking records , EVB
 * EVB    a booking record for an event,  an object
 * NBI    a new booking information for one user
 * BLC    a collection of bookings, collection of BLI
 * BLI    the user information booking for a user
 * WLC    a collection of waiting records, collection of WLI
 * WLI    the user information about someone on waiting list
 */

namespace Ramblers\Component\Ra_eventbooking\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use \Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
//use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;

/**
 * Class Ra_eventbookingFrontendHelper
 *
 * @since  1.0.0
 */
class Ra_eventbookingHelper {

    /**
     * Gets the files attached to an item
     *
     * @param   int     $pk     The item's id
     *
     * @param   string  $table  The table's name
     *
     * @param   string  $field  The field's name
     *
     * @return  array  The files
     */
    public static function getFiles($pk, $table, $field) {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);

        $query
                ->select($field)
                ->from($table)
                ->where('id = ' . (int) $pk);

        $db->setQuery($query);

        return explode(',', $db->loadResult());
    }

    /**
     * Gets the edit permission for an user
     *
     * @param   mixed  $item  The item
     *
     * @return  bool
     */
    public static function canUserEdit($item) {
        $permission = false;
        $user = Factory::getApplication()->getIdentity();

        if ($user->authorise('core.edit', 'com_ra_eventbooking') || (isset($item->created_by) && $user->authorise('core.edit.own', 'com_ra_eventbooking') && $item->created_by == $user->id) || $user->authorise('core.create', 'com_ra_eventbooking')) {
            $permission = true;
        }

        return $permission;
    }

    public static function loadScripts() {
        \RLoad::addStyleSheet("media/com_ra_eventbooking/css/style.css");
        \RLoad::addScript("media/com_ra_eventbooking/js/ra.bookings.js");
        \RLoad::addScript("media/com_ra_eventbooking/js/ra.bookings.displayEvents.js");
        \RLoad::addScript("media/com_ra_eventbooking/js/ra.bookings.general.js");
        \RLoad::addScript("media/com_ra_eventbooking/js/ra.bookings.form.js");
        \RLoad::addScript("media/com_ra_eventbooking/js/blue/md5.min.js");
        \RLoad::addScript("https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js");
        \RLoad::addStyleSheet("https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css", "text/css");
    }

    public static function getPostedData() {
        $input = Factory::getApplication()->getInput();
        // Retrieve individual parameters
        $jsonData = $input->POST->get('data', '', 'raw');
        $data = \json_decode($jsonData);
        // Check if decoding was successful
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON data received.');
        }
        return $data;
    }

    public static function getEventsWithBooking() {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $names = array('event_id');
        $query->select($db->quoteName($names));
        $query->from($db->quoteName('#__ra_event_bookings'));
        $query->where($db->quoteName('state') . ' = 1 ');

        $db->setQuery($query);
        $result = $db->loadColumn();
        if ($result !== null) {
            
        }

        return $result;
    }

    public static function getNewBooking($id, $name, $email, $attendees, $paid, $mode) {
        return new bli($id, $name, $email, $attendees, $paid, $mode);
    }

    public static function getNewWaiting($id, $name, $email, $mode) {
        return new wli($id, $name, $email, $mode);
    }

    public static function getEVBrecord($ewid, $mode) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $names = array('max_places', 'booking_data', 'waiting_data', 'event_data',
            'payment_required', 'payment_details',
            'booking_contact_id');
        $query->select($db->quoteName($names));
        $query->from($db->quoteName('#__ra_event_bookings'));
        $query->where($db->quoteName('state') . ' = 1 ');
        $query->where($db->quoteName('event_id') . ' = ' . $ewid . ' ');

        $db->setQuery($query);
        $result = $db->loadObject();
        if ($result !== null) {
            $result->event_id = $ewid;
// check payment_required
            $result->payment_required = $result->payment_required == "1";
// check booking data

            $bookings = new blc();
            $bookings->process($result->booking_data, $mode);
            $result->blc = $bookings;
            unset($result->booking_data);

            $waiting = new wlc();
            $waiting->process($result->waiting_data, $mode);
            $result->wlc = $waiting;
            unset($result->waiting_data);

            $event = new eventData();
            $event->process($result->event_data);

            $result->event_data = $event;
        }
        return $result;
    }

    public static function getAllEVBRecords() {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $names = array('event_id', 'max_places', 'booking_data', 'waiting_data', 'event_data',
            'payment_required', 'payment_details',
            'booking_contact_id');
        $query->select($db->quoteName($names));
        $query->from($db->quoteName('#__ra_event_bookings'));
        $query->where($db->quoteName('state') . ' = 1 ');
// loop events!
        $db->setQuery($query);
        $rows = $db->loadObjectList();

//     $events->process($rows);
        foreach ($rows as $row) {
            $row->payment_required = $row->payment_required == "1";

            $bookings = new blc();
            $bookings->process($row->booking_data, 'Summary');
            $row->blc = $bookings;

            $waiting = new wlc();
            $waiting->process($row->waiting_data, 'Summary');
            $row->wlc = $waiting;

            $event = new eventData();
            $event->process($row->event_data);
            $row->event_data = $event;
        }

        return $rows;
    }

    public static function checkBooking($evb, $newBooking) {
        $currentNoAttendees = $evb->blc->noAttendees();
        $extraPlaces = $newBooking->noAttendees();
// check if use has existing booking
        $currentBooking = $evb->blc->hasBooking($newBooking->email);
        if ($currentBooking !== null) {
            $extraPlaces = $extraPlaces - $currentBooking->noAttendees();
        }
//  calc remaining
        $maxPlaces = $evb->max_places;
        If ($maxPlaces === 0) {
            $maxPlaces = PHP_INT_MAX;
        }
// check booking does not go over max allowed   
        if ($extraPlaces + $currentNoAttendees > $maxPlaces) {
            throw new \RuntimeException('Not enough spare places to make this booking');
        }
    }

    public static function updateBooking($evb, $newBooking) {
        $bookings = $evb->blc;
        $bookings->removeItem($newBooking->email); // remove old booking if there is one
        if ($newBooking->noAttendees() > 0) {
            $bookings->addItem($newBooking);
        }
    }

    public static function removeBooking($evb, $md5Email) {
        $bookings = $evb->blc;
        $bookings->removeItem($md5Email); // remove old booking if there is one
    }

    public static function updateWaiting($evb, $newBooking) {
        $waiting = $evb->wlc;
        $waiting->removeItem($newBooking->email); // remove old booking if there is one
        $waiting->addItem($newBooking);
    }

    public static function removeWaiting($evb, $md5Email) {
        $waiting = $evb->blc;
        $waiting->removeItem($md5Email);
    }

    public static function updateDBField($event_id, $field, $value, $type = "string") {
        $varType = \Joomla\Database\ParameterType::STRING;
        switch ($type) {
            case 'int':
                $varType = \Joomla\Database\ParameterType::INTEGER;
                break;
            case 'string':
                $varType = \Joomla\Database\ParameterType::STRING;
                break;
            default:
                throw new \RuntimeException('App error in updateDBField');
        }
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true);

// Fields to update.
        $fields = array(
            $db->quoteName($field) . ' = :field'
        );

// Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('event_id') . ' = :event_id'
        );

        $query->update($db->quoteName('#__ra_event_bookings'))->set($fields)->where($conditions);

        $query
                ->bind(':field', $value, $varType)
                ->bind(':event_id', $event_id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);

        $result = $db->execute();

        if (!$result) {
            throw new \RuntimeException('Unknow error while updating database');
        }
    }

    public static function getGroupContact() {
        // return who user should reply to
        $componentParams = ComponentHelper::getParams('com_ra_eventbooking');
        $id = $componentParams->get('group_booking_contact', 0);
        If ($id === 0) {
            throw new \RuntimeException('Group Booking Contact not set - contact group');
        }
        $juser = Factory::getUser($id);
        $user = (object) ['name' => $juser->name,
                    'email' => $juser->email];
        return $user;
    }

    public static function getEventContact($evb) {
        // return who user should reply to
        $componentParams = ComponentHelper::getParams('com_ra_eventbooking');
        $id = $componentParams->get('group_booking_contact', 0);
        If ($id === 0) {
            throw new \RuntimeException('Group Booking Contact not set - contact group');
        }
        $juser = Factory::getUser($id);
        $name = $juser->name;
        $email = $juser->email;
        if ($evb->booking_contact_id !== 0) {
            $euser = Factory::getUser($id);
            $name = $euser->name;
            $email = $euser->email;
        }
        $user = (object) ['name' => $name,
                    'email' => $email];
        return $user;
    }

    public static function sendEmailsToUser($sendToArray, $copy, $replyTo, $subject, $content, $attach = null) {
        $config = Factory::getConfig();
        $sender = array(
            $config->get('mailfrom'),
            $config->get('fromname')
        );

        $container = Factory::getContainer();
        $mailer = $container->get(MailerFactoryInterface::class)->createMailer();
        //$mailer = Factory::getMailer();
        $mailer->isHtml(true);
        $mailer->Encoding = '8bit';
        $mailer->setSender($sender);
        if ($replyTo !== null) {
            $mailer->addReplyTo($replyTo->email, $replyTo->name);
        }
        $mailer->setSubject($subject);
        if ($attach !== null) {
            if ($attach->type === 'string') {
                self::addStringAttachment($mailer, $attach);
            }
        }
        foreach ($sendToArray as $sendTo) {
            $mailer->clearAllRecipients();
            $mailer->addRecipient($sendTo->email, $sendTo->name);
            if ($copy !== null) {
                $mailer->addCC($copy->email, $copy->name);
            }
            $body = $content;
            $body = str_replace("{toName}", $sendTo->name, $body);
            $body = str_replace("{toEmail}", $sendTo->email, $body);
            $body = str_replace("{attendees}", $sendTo->noAttendees(), $body);
            $body = str_replace("{replyToName}", $replyTo->name, $body);
            $mailer->setBody($body);
            $send = $mailer->Send();
            if (!$send) {
                Log::add('Unable to send email to ' . $sendTo->name, Log::ERROR, 'com_ra_eventbooking');
            }
        }
    }

    public static function sendEmailfromUser($sendTo, $copy, $replyTo, $subject, $content, $attach = null) {
        $config = Factory::getConfig();
        $sender = array(
            $config->get('mailfrom'),
            $config->get('fromname')
        );

        $container = Factory::getContainer();
        $mailer = $container->get(MailerFactoryInterface::class)->createMailer();
        $mailer->isHtml(true);
        $mailer->Encoding = '8bit';
        $mailer->setSender($sender);
        if ($replyTo !== null) {
            $mailer->addReplyTo($replyTo->email, $replyTo->name);
        }
        $mailer->setSubject($subject);
        if ($attach !== null) {
            if ($attach->type === 'string') {
                self::addStringAttachment($mailer, $attach);
            }
        }

        //    $mailer->clearAllRecipients();
        $mailer->addRecipient($sendTo->email, $sendTo->name);
        if ($copy !== null) {
            $mailer->addCC($copy->email, $copy->name);
        }
        $body = $content;
        $body = str_replace("{toName}", $sendTo->name, $body);
        $body = str_replace("{toEmail}", $sendTo->email, $body);
        //     $body = str_replace("{attendees}", $sendTo->noAttendees(), $body);
        $body = str_replace("{replyToName}", $replyTo->name, $body);
        $mailer->setBody($body);
        $send = $mailer->Send();
        if (!$send) {
            Log::add('Unable to send email to ' . $sendTo->name, Log::ERROR, 'com_ra_eventbooking');
        }
    }

    private static function addStringAttachment($mailer, $attach) {
        // Your string content, e.g. ICS
        $filename = $attach->filename;
        $encoding = $attach->encoding;
        $mimeType = $attach->mimeType;
        $contents = $attach->data;

        // Get Joomla tmp path from configuration
        $config = Factory::getConfig();
        $tmpPath = rtrim($config->get('tmp_path'), '/');  // e.g. /path/to/site/tmp
        // Build a unique filename
        $file = $tmpPath . '/ics_' . uniqid() . '.ics';

        // Write the string into the file
        file_put_contents($file, $contents);

        // Now $file is a real file you can attach:
        $mailer->addAttachment($file, $filename, $encoding, $mimeType);

        // Optionally delete after sending:
        //  @unlink($file);
    }

    public static function getEmailTitle($action, $ew) {
        $componentParams = ComponentHelper::getParams('com_ra_eventbooking');
        $title = $componentParams->get('group_email_format', '{yy/mm/dd} {action} {title}');

        $date = new \DateTime($ew->basics->walkDate);

        $title = str_replace("{date}", $date->format("D, jS M Y"), $title);
        $title = str_replace("{yyyy/mm/dd}", $date->format('Y/m/d'), $title);
        $title = str_replace("{yy/mm/dd}", $date->format('y/m/d'), $title);
        $title = str_replace("{action}", $action, $title);
        $title = str_replace("{title}", $ew->basics->title, $title);
        return $title;
    }

    public static function getEmailTemplate($template, $ew) {
        $filePath = JPATH_COMPONENT . '/src/Helper/templates/' . $template;
        $content = \file_get_contents($filePath);
        if (!$content) {
            throw new \RuntimeException('Unable to get content of email:' . $template);
        }
        if ($ew === null) {
            return $content;
        }
        $date = new \DateTime($ew->basics->walkDate);
        $walkDate = $date->format("D, jS M Y");
        $config = Factory::getConfig();
        $siteName = $config->get('sitename');
        $search = ["{eventId}", "{eventType}", "{eventDate}", "{eventTitle}",
            "{eventDescription}", "{eventDescriptionHtml}", "{nationalUrl}", "{localPopupUrl}",
            "{groupName}", "{dateUpdated}", "{siteName}"];
        $replace = [$ew->admin->id, strtolower($ew->admin->eventType), $walkDate,
            $ew->basics->title, $ew->basics->description, $ew->basics->descriptionHtml,
            $ew->admin->nationalUrl, $ew->admin->localPopupUrl,
            $ew->admin->groupName, $ew->admin->dateUpdated, $siteName];
        return str_replace($search, $replace, $content);
    }

    public static function updateEmailforBooking($content, $evb) {

        $totalPlacesAvailable = $evb->max_places;
        if ($totalPlacesAvailable === 0) {
            $totalPlacesAvailable = 'Unlimited places avaiable';
            $placesAvailable = 'Unlimited';
            $placesTaken = $evb->blc->noAttendees();
        } else {
            $placesAvailable = $totalPlacesAvailable - $placesTaken;
            $placesTaken = $evb->blc->noAttendees();
        }
        $search = ["{placesAvailable}", "{placesTaken}", "{totalPlacesAvailable}"];
        $replace = [$placesAvailable, $placesTaken,
            $totalPlacesAvailable];
        return str_replace($search, $replace, $content);
    }

    public static function getUserData() {
        $juser = Factory::getUser();
        $user = new class {
            
        };
        $user->id = $juser->id;
        $user->name = $juser->name;
        $user->email = md5($juser->email);
        if ($user->id > 0) {
            $user->canEdit = $juser->authorise('core.edit', 'com_ra_eventbooking');
        }
        return $user;
    }

    public static function getSendTo($name, $email) {
        return new bli(0, $name, $email, '', 0, false, 'Internal');
    }

    public static function canEdit() {
        $juser = Factory::getUser();
        if ($juser->id > 0) {
            return $juser->authorise('core.edit', 'com_ra_eventbooking');
        }
        return false;
    }

    public static function getSettings() {
        $componentParams = ComponentHelper::getParams('com_ra_eventbooking');
        $settings = new class {
            
        };
        $id = $componentParams->get('group_booking_contact', 0);
        If ($id === 0) {
            throw new \RuntimeException('Group Booking Contact not set - contact group');
        }
        $juser = Factory::getUser($id);
        $settings->group_contact_name = $juser->name;
        $settings->group_contact_email = md5($juser->email);
        $settings->guest = $componentParams->get('guest') === "1";
        $settings->maxattendees = intval($componentParams->get('maxattendees', 0));
        $settings->waitinglist = $componentParams->get('waitinglist') === "1";
        $settings->maxguestattendees = intval($componentParams->get('maxguestattendees', 1));
        $settings->userlistvisibletousers = $componentParams->get('userlistvisibletousers') === "1";
        $settings->userlistvisibletoguests = $componentParams->get('userlistvisibletoguests') === "1";
        return $settings;
    }

    public static function createBlc() {
        return new blc();
    }

    public static function createEventData($date, $title, $dateUpdated, $localPopupUrl) {
        $ed = new eventData();
        $ed->setValues($date, $title, $dateUpdated, $localPopupUrl);
        return $ed;
    }
}

class blc implements \JsonSerializable {

    private $items = [];

    public function addItem($bli) {
        array_push($this->items, $bli);
    }

    public function removeItem($email) {
        foreach ($this->items as $key => $item) {
            if ($item->email === $email) {
                unset($this->items[$key]);
            }
        }
    }

    public function getItemByMd5Email($md5email) {
        foreach ($this->items as $key => $item) {
            if ($item->getMd5Email() === $md5email) {
                return $this->items[$key];
            }
        }
        return null;
    }

    public function removeItemByMd5Email($md5email) {
        foreach ($this->items as $key => $item) {
            if ($item->getMd5Email() === $md5email) {
                unset($this->items[$key]);
            }
        }
    }

    public function getItems() {
        return $this->items;
    }

    public function process($jsonValue, $mode) {
        if ($jsonValue === null) {
            return;
        }
        $values = \json_decode($jsonValue);
        foreach ($values as $value) {
            $this->addItem(new bli($value->id, $value->name, $value->email, $value->noAttendees, $value->paid, $mode));
        }
    }

    public function noAttendees() {
        $no = 0;
        foreach ($this->items as $item) {
            $no += $item->noAttendees();
        }
        return $no;
    }

    public function hasBooking($email) {
        foreach ($this->items as $item) {
            if ($item->email === $email) {
                return $item;
            }
        }
        return null;
    }

    public function getArray() {
        $to = [];
        foreach ($this->items as $item) {
            $to[] = $item;
        }
        return $to;
    }

    public function getBookingTable($paid = false, $canEdit = false) {
        $out = "<table>";
        $out .= "<caption>";
        $out .= "Booking list for this event";
        $out .= "</caption>";
        $out .= "</caption>";
        $out .= "<thead><tr>";
        $out .= "<th>Name</th>";
        if ($canEdit) {
            $out .= "<th>Email</th>";
        }
        $out .= "<th>Attendees</th>";
        if ($paid) {
            $out .= "<th>Paid</th>";
        }
        $out .= "</tr></thead>";
        $out .= "<tbody>";

        foreach ($this->items as $item) {
            $out .= $item->getTableRow($paid, $canEdit);
        }
        $out .= "</tbody>";
        $out .= "</table>";
        return $out;
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->items;
    }
}

// Booking
class bli implements \JsonSerializable {

    private $id;
    public readonly string $name;
    public readonly string $email;
    private $noAttendees;
    private $paid;
    private $mode; // 'Summary', 'Single' or 'Internal'

    public function __construct($id, $name, $email, $attendees, $paid, $mode) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->noAttendees = intval($attendees);
        $this->setPaid($paid);
        $this->mode = $mode;
    }

    public function isPresent($email) {
        return $email === $this->email;
    }

    public function noAttendees() {
        return $this->noAttendees;
    }

    public function setPaid($value) {
        $this->paid = $value;
        if ($value === "") {
            $this->paid = "Zero";
        }
    }

    public function getMd5Email() {
        return md5($this->email);
    }

    public function getTableRow($paid, $canEdit) {
        $out = "<tr>";
        $out .= '<td>' . $this->name . '</td>';
        if ($canEdit) {
            $out .= '<td>' . $this->email . '</td>';
        }
        $out .= '<td>' . $this->noAttendees . '</td>';
        if ($paid) {
            $out .= '<td>' . $this->paid . '</td>';
        }
        $out .= "</tr>";
        return $out;
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        switch ($this->mode) {
            case "Summary":
                return [
                    "noAttendees" => $this->noAttendees,
                ];
            case "Single":
                return [
                    "id" => $this->id,
                    "name" => $this->name,
                    "md5Email" => md5($this->email),
                    "noAttendees" => $this->noAttendees,
                    "paid" => $this->paid,
                ];
            case "Internal":
                return [
                    "id" => $this->id,
                    "name" => $this->name,
                    "email" => $this->email,
                    "noAttendees" => $this->noAttendees,
                    "paid" => $this->paid,
                ];
            default:
                return [];
        }
    }
}

class wlc implements \JsonSerializable {

    public $items = [];

    public function addItem($wli) {
        array_push($this->items, $wli);
    }

    public function getItems() {
        return $this->items;
    }

    public function process($jsonValue, $mode) {
        if ($jsonValue === null) {
            return;
        }
        $values = \json_decode($jsonValue);
        foreach ($values as $value) {
            $this->addItem(new wli($value->id, $value->name, $value->email, $mode));
        }
    }

    public function isWaiting($email) {
        foreach ($this->items as $item) {
            if ($item->isWaiting($email)) {
                return $item;
            }
        }
        return null;
    }

    public function remove($email) {
        foreach ($this->items as $key => $item) {
            if ($item->isWaiting($email)) {
                unset($this->items[$key]);
                return true;
            }
        }
        return false;
    }

    public function getItemByMd5Email($md5email) {
        foreach ($this->items as $key => $item) {
            if ($item->getMd5Email() === $md5email) {
                return $this->items[$key];
            }
        }
        return null;
    }

    public function removeItemByMd5Email($md5email) {
        foreach ($this->items as $key => $item) {
            if ($item->getMd5Email() === $md5email) {
                unset($this->items[$key]);
            }
        }
    }

    public function getArray() {
        $to = [];
        foreach ($this->items as $item) {
            $to[] = $item;
        }
        return $to;
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->items;
    }
}

// Waiting list
class wli implements \JsonSerializable {

    private $id;
    public readonly string $name;
    public readonly string $email;
    private $mode;

    public function __construct($id, $name, $email, $mode) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->mode = $mode;
    }

    public function getMd5Email() {
        return md5($this->email);
    }

    public function noAttendees() {
        return 'Waiting list only/Not booked';
    }

    public function isWaiting($email) {
        return $email === $this->email;
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        switch ($this->mode) {
            case "Summary":
            case "Single":
                return [
                    "id" => $this->id,
                    "name" => $this->name,
                    "md5Email" => md5($this->email),
                ];
            case "Internal":
                return [
                    "id" => $this->id,
                    "name" => $this->name,
                    "email" => $this->email,
                ];
            default:
                return [];
        }
    }
}

class eventData implements \JsonSerializable {

    private $date = '';
    private $title = '';
    private $dateUpdated = '2025-01-01T00:00:00.000Z';
    private $localPopupUrl = '';

    public function __construct() {
        
    }

    public function setValues($date, $title, $dateUpdated, $localPopupUrl) {
        $this->date = $date;
        $this->title = $title;
        $this->dateUpdated = $dateUpdated;
        $this->localPopupUrl = $localPopupUrl;
    }

    public function process($jsonValue) {
        if ($jsonValue === null) {
            return;
        }
        $value = \json_decode($jsonValue);
        if ($value !== '') {
            $this->date = $value->date;
            $this->title = $value->title;
            $this->dateUpdated = $value->dateUpdated;
            $this->localPopupUrl = $value->localPopupUrl;
        }
    }

    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    public function setDateUpdated($value) {
        $this->dateUpdated = $value;
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        return [
            "date" => $this->date, // first to allow sort on date
            "title" => $this->title,
            "dateUpdated" => $this->dateUpdated,
            "localPopupUrl" => $this->localPopupUrl
        ];
    }
}
