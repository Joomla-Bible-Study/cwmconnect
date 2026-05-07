<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Utilities\ArrayHelper;

/**
 * Members list controller.
 *
 * @since  2.0.0
 */
class MembersController extends AdminController
{
    /**
     * @var string
     * @since 2.0.0
     */
    protected $text_prefix = 'COM_CHURCHDIRECTORY_MEMBERS';

    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * @param   mixed                $factory  The factory.
     * @param   mixed                $app      The Application object.
     * @param   mixed                $input    Input.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function __construct($config = [], $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        $this->registerTask('unfeatured', 'featured');
    }

    /**
     * Method to toggle the featured setting of a list of members.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function featured(): void
    {
        $this->checkToken();

        $user   = $this->app->getIdentity();
        $ids    = (array) $this->input->get('cid', [], 'array');
        $values = ['featured' => 1, 'unfeatured' => 0];
        $task   = $this->getTask();
        $value  = ArrayHelper::getValue($values, $task, 0, 'int');

        /** @var \CWM\Component\Churchdirectory\Administrator\Model\MemberModel $model */
        $model = $this->getModel();

        foreach ($ids as $i => $id) {
            $item = $model->getItem($id);

            if (
                $user === null
                || !$user->authorise('core.edit.state', 'com_churchdirectory.category.' . (int) $item->catid)
            ) {
                unset($ids[$i]);
                $this->app->enqueueMessage(
                    Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'),
                    'notice'
                );
            }
        }

        if (empty($ids)) {
            $this->app->enqueueMessage(Text::_('COM_CHURCHDIRECTORY_NO_ITEM_SELECTED'), 'error');
        } elseif (!$model->featured($ids, $value)) {
            $this->app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect('index.php?option=com_churchdirectory&view=members');
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name     The model name.
     * @param   string  $prefix   The class prefix.
     * @param   array   $config   Configuration array.
     *
     * @return  BaseDatabaseModel
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Member', $prefix = 'Administrator', $config = ['ignore_request' => true]): BaseDatabaseModel
    {
        return parent::getModel($name, $prefix, $config);
    }
}
