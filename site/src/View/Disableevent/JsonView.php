<?php

/*
 * DisableEvent
 *      parameters
 *         POST data
 *             id - id of event
 *           
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=Disableevent&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Disableevent;

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
            helper::updateDBField($ewid, 'state', 0, 'int');
            $record = new \stdClass();
            $record->feedback = $feedback;
            $record->ewid = $ewid;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
