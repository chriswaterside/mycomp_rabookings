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
use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_ra_eventbooking', JPATH_SITE);

$user    = Factory::getApplication()->getIdentity();
$canEdit = Ra_eventbookingHelper::canUserEdit($this->item, $user);


?>

<div class="eventsetting-edit front-end-edit">

<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
    <?php endif;?>
	<?php if (!$canEdit) : ?>
		<h3>
		<?php throw new \Exception(Text::_('COM_RA_EVENTBOOKING_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?>
		</h3>
	<?php else : ?>
		<?php if (!empty($this->item->id)): ?>
			<h1><?php echo Text::sprintf('COM_RA_EVENTBOOKING_EDIT_ITEM_TITLE', $this->item->id); ?></h1>
		<?php else: ?>
			<h1><?php echo Text::_('COM_RA_EVENTBOOKING_ADD_ITEM_TITLE'); ?></h1>
		<?php endif; ?>

		<form id="form-eventsetting"
			  action="<?php echo Route::_('index.php?option=com_ra_eventbooking&task=eventsettingform.save'); ?>"
			  method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
			
	<input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />

	<input type="hidden" name="jform[state]" value="<?php echo isset($this->item->state) ? $this->item->state : ''; ?>" />

				<?php echo $this->form->getInput('modified_by'); ?>
	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'eventsrestriction')); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'eventsrestriction', Text::_('COM_RA_EVENTBOOKING_TAB_EVENTSRESTRICTION', true)); ?>
	<?php echo $this->form->renderField('created_by'); ?>

	<?php echo $this->form->renderField('event_id'); ?>

	<?php echo $this->form->renderField('max_places'); ?>

	<?php echo $this->form->renderField('payment_required'); ?>

	<?php echo $this->form->renderField('payment_details'); ?>

	<?php echo $this->form->renderField('creation_date'); ?>

	<?php echo $this->form->renderField('event_contact_name'); ?>

	<?php echo $this->form->renderField('event_contact_email'); ?>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<div class="control-group">
				<div class="controls">

					<?php if ($this->canSave): ?>
						<button type="submit" class="validate btn btn-primary">
							<span class="fas fa-check" aria-hidden="true"></span>
							<?php echo Text::_('JSUBMIT'); ?>
						</button>
					<?php endif; ?>
					<a class="btn btn-danger"
					   href="<?php echo Route::_('index.php?option=com_ra_eventbooking&task=eventsettingform.cancel'); ?>"
					   title="<?php echo Text::_('JCANCEL'); ?>">
					   <span class="fas fa-times" aria-hidden="true"></span>
						<?php echo Text::_('JCANCEL'); ?>
					</a>
				</div>
			</div>

			<input type="hidden" name="option" value="com_ra_eventbooking"/>
			<input type="hidden" name="task"
				   value="eventsettingform.save"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
