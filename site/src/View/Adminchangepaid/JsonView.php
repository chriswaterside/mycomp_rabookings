<?php

/*
 * Admin Change Paid status
 *      parameters
 *         POST data
 *             id - id of event
 *             event - json version of walk/event Ramblers-webs format
 *             amount - amount paid
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=adminchangepaid&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Adminchangepaid;

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

            $data = helper::getPostedData();
            $md5Email = $data->md5Email;
            $ewid = $data->ewid;
            $paid = $data->paid;
            $ebRecord = helper::getEVBrecord($ewid, "Internal");
            $item = $ebRecord->blc->getItemByMd5Email($md5Email);
            if ($item === null) {
                throw new \RuntimeException('Unable to find the booking for this person');
            }
            $feedback[] = '<h3>The amount paid has been set to <b>' . $paid . '</b></h3>';
            $item->setPaid($paid);
            helper::updateDBField($ewid, 'booking_data', json_encode($ebRecord->blc), 'string');

            $record = new \stdClass();
            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
