<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Single-item model for feed token CRUD.
 *
 * On new-record saves the model generates the token pair via
 * {@see FeedTokenService::generate()} and stashes the one-time cleartext
 * in the user session so the redirect target can display it.
 *
 * @since  __DEPLOY_VERSION__
 */
class FeedtokenModel extends AdminModel
{
    /** @since __DEPLOY_VERSION__ */
    public $typeAlias = 'com_cwmconnect.feedtoken';

    /**
     * @param   string  $name     Table name.
     * @param   string  $prefix   Class prefix.
     * @param   array   $options  Configuration array.
     *
     * @return  Table
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getTable($name = 'FeedToken', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * @param   array  $data      Data for the form.
     * @param   bool   $loadData  True to load the form data.
     *
     * @return  Form|false
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        return $this->loadForm(
            'com_cwmconnect.feedtoken',
            'feedtoken',
            ['control' => 'jform', 'load_data' => $loadData],
        );
    }

    /**
     * @return  mixed
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function loadFormData(): mixed
    {
        $data = Factory::getApplication()->getUserState('com_cwmconnect.edit.feedtoken.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Generate token hash on new records before persisting.
     *
     * @param   array  $data  Form data.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function save($data): bool
    {
        $isNew = empty($data['id']);

        if ($isNew) {
            $service             = new FeedTokenService($this->getDatabase());
            $pair                = $service->generate();
            $data['token_hash']  = $pair['hash'];

            Factory::getApplication()->setUserState(
                'com_cwmconnect.feedtoken.cleartext',
                $pair['cleartext'],
            );
        }

        return parent::save($data);
    }
}
