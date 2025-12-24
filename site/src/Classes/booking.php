<?php

/*
 * copyright: Chris Vaughan
 * email: ruby->tuesday@ramblers-webs->org->uk
 */
namespace Ramblers\Component\Ra_eventbooking\Site\Classes\bookings;

class bookingCollection {

    private $items = [];

    public function addItem($b) {
        $this->items->push($b);
    }

    public function process($values) {
        foreach ($values as $value) {
            $this->addItem(new booking($value));
        }
    }

    public function noAttendees() {
        $no = 0;
        foreach ($this->items as $b) {
            $no += $b->noAttendees();
        }
        return $no;
    }

    public function hasBooking($email) {
        foreach ($this->items as $item) {
            if ($item->hasBooking($email)) {
                return $item;
            }
        }
        return null;
    }
}



// Booking
class booking {

    public function __construct($value) {
        $this->_id = $value->userId;
        $this->_name = $value->userName;
        $this->_email = $value->userEmail;
        $this->_noAttendees = $value->attendees;
        $this->_paid = $value->paid;
    }

    public function setMode($mode) {
        $this->mode = $mode;
    }

    public function isPresent($email) {
        return $email === $this->_email;
    }

    public function noAttendees() {
        return $this->_noAttendees;
    }

    public function jsonSerialize(): mixed {
        if ($this->mode === "Summary") {
            return [
                "noAttendees" => $this->attendees,
            ];
        } else {
            return [
                "id" => $this->userId,
                "name" => $this->userName,
                "email" => md5($this->userEmail),
                "noAttendees" => $this->attendees,
                "paid" => $this->paid,
            ];
        }
    }
}
