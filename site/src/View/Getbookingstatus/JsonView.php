<?php

/*
 * get the booking information for an event
 *      parameters
 *         POST data
 *             id - id of event
 *             event - json version of walk/event Ramblers-webs format
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=getbookingstatus&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Getbookingstatus;

use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {

        try {
            $feedback = [];
            $data = helper::getPostedData();
            $ewid = $data->ewid;
            // throw new \RuntimeException('Test error handling in JS code');
            $record = new \stdClass();
            $record->feedback = $feedback;
            $record->evb = helper::getEVBrecord($ewid, "Single");
            $record->settings = helper::getSettings();
            $record->user = helper::getUserData();
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
