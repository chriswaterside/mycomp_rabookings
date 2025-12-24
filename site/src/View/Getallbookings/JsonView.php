<?php

/*
 * get booking information summary for ALL events
 *      parameters
 *      only valid for logged in users   
 * 
 *      url
 *         index.php?option=com_ra_eventbooking&view=getallbookings&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_eventbooking\Site\View\Getallbookings;

use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {

        // need to add check that user is logged in

        try {
            $feedback=[];
            $record = new \stdClass();
            $record->esc = helper::getAllEVBRecords();
            $record->settings = helper::getSettings();
            $record->user = helper::getUserData();

            if ($record->user->id === 0) {
                $record = null;
                throw new \RuntimeException('Invalid user access');
            }
            if ($record->user->id > 0 and !$record->user->canEdit) {
                $record = null;
                throw new \RuntimeException('Invalid uses access level');
            }

            $record->feedback = $feedback;
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
