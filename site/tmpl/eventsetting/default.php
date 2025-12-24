<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_eventbooking
 * @author     Chris Vaughan  <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2025 Ruby Tuesday
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

$canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_ra_eventbooking');

if (!$canEdit && Factory::getApplication()->getIdentity()->authorise('core.edit.own', 'com_ra_eventbooking'))
{
	$canEdit = Factory::getApplication()->getIdentity()->id == $this->item->created_by;
}
?>

<div class="item_fields">
<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
    <?php endif;?>
	<table class="table">
		

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_CREATED_BY'); ?></th>
			<td><?php echo $this->item->created_by_name; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_EVENT_ID'); ?></th>
			<td><?php echo $this->item->event_id; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_MAX_PLACES'); ?></th>
			<td><?php echo $this->item->max_places; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_PAYMENT_REQUIRED'); ?></th>
			<td><?php echo $this->item->payment_required; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_PAYMENT_DETAILS'); ?></th>
			<td><?php echo nl2br($this->item->payment_details); ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_CREATION_DATE'); ?></th>
			<td><?php echo $this->item->creation_date; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_EVENT_CONTACT_NAME'); ?></th>
			<td><?php echo $this->item->event_contact_name; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_EVENTBOOKING_FORM_LBL_EVENTSETTING_EVENT_CONTACT_EMAIL'); ?></th>
			<td><?php echo $this->item->event_contact_email; ?></td>
		</tr>

	</table>

</div>

<?php $canCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_ra_eventbooking.' . $this->item->id) || $this->item->checked_out == Factory::getApplication()->getIdentity()->id; ?>
	<?php if($canEdit && $this->item->checked_out == 0): ?>

	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_ra_eventbooking&task=eventsetting.edit&id='.$this->item->id); ?>"><?php echo Text::_("COM_RA_EVENTBOOKING_EDIT_ITEM"); ?></a>
	<?php elseif($canCheckin && $this->item->checked_out > 0) : ?>
	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_ra_eventbooking&task=eventsetting.checkin&id=' . $this->item->id .'&'. Session::getFormToken() .'=1'); ?>"><?php echo Text::_("JLIB_HTML_CHECKIN"); ?></a>

<?php endif; ?>

<?php if (Factory::getApplication()->getIdentity()->authorise('core.delete','com_ra_eventbooking.eventsetting.'.$this->item->id)) : ?>

	<a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
		<?php echo Text::_("COM_RA_EVENTBOOKING_DELETE_ITEM"); ?>
	</a>

	<?php echo HTMLHelper::_(
                                    'bootstrap.renderModal',
                                    'deleteModal',
                                    array(
                                        'title'  => Text::_('COM_RA_EVENTBOOKING_DELETE_ITEM'),
                                        'height' => '50%',
                                        'width'  => '20%',
                                        
                                        'modalWidth'  => '50',
                                        'bodyHeight'  => '100',
                                        'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_ra_eventbooking&task=eventsetting.remove&id=' . $this->item->id, false, 2) .'" class="btn btn-danger">' . Text::_('COM_RA_EVENTBOOKING_DELETE_ITEM') .'</a>'
                                    ),
                                    Text::sprintf('COM_RA_EVENTBOOKING_DELETE_CONFIRM', $this->item->id)
                                ); ?>

<?php endif; ?>