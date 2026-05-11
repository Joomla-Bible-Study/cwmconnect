<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Administrator\Helper\ReportbuildHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * Reports model — assembles the directory dataset that backs the export
 * tasks (CSV / KML / PDF / missing-photos).
 *
 * @since  2.0.0
 */
class ReportsModel extends ListModel
{
    /**
     * @var string
     * @since 2.0.0
     */
    public $typeAlias = 'com_churchdirectory.reports';

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @throws \Exception
     * @since   2.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'alias', 'a.alias',
                'published', 'a.published',
                'access', 'a.access',
                'language', 'a.language',
                'category_title', 'c.title',
                'mstatus', 'a.mstatus',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to get the row form.
     *
     * @param   array  $data      Data for the form.
     * @param   bool   $loadData  True if the form is to load its own data.
     *
     * @return  Form|false
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getForm(array $data = [], bool $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_churchdirectory.reports',
            'reports',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?: false;
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   Ordering
     * @param   string  $direction  Direction
     *
     * @return  void
     *
     * @throws \Exception
     * @since   2.0.0
     */
    protected function populateState($ordering = 'a.id', $direction = 'asc'): void
    {
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_churchdirectory');

        $format = (string) $app->getInput()->getWord('format', '');
        $limit  = $format === 'feed' ? (int) $app->get('feed_limit') : 0;
        $this->setState('list.limit', $limit);

        $limitstart = $app->getInput()->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        $menuParams = new Registry();

        if ($menu = $app->getMenu()?->getActive()) {
            $menuParams->loadString($menu->params);
        }

        $mergedParams = clone $params;
        $mergedParams->merge($menuParams);

        $orderCol = $app->getInput()->get('filter_order', $mergedParams->get('dinitial_sort', 'ordering'));
        $this->setState('list.ordering', $orderCol);

        $listOrder = strtoupper((string) $app->getInput()->get('filter_order_Dir', 'ASC'));

        if (!\in_array($listOrder, ['ASC', 'DESC', ''], true)) {
            $listOrder = 'ASC';
        }

        $this->setState('list.direction', $listOrder);
        $this->setState('category.id', $app->getInput()->get('id', 0, 'int'));

        $user = $app->getIdentity();

        if ($user && !$user->authorise('core.edit.state', 'com_churchdirectory') && !$user->authorise('core.edit', 'com_churchdirectory')) {
            $this->setState('filter.published', 1);
            $this->setState('filter.publish_date', true);
        }

        $this->setState('filter.mstatus', $app->getInput()->get('filter_mstatus', $mergedParams->get('mstatus', '0')));
        $this->setState('filter.order', $app->getInput()->get('filter_order', $mergedParams->get('order', 'a.id')));
        $this->setState('params', $params);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
     *
     * @since   2.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $app   = Factory::getApplication();
        $user  = $app->getIdentity();
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($this->getState('item.select', 'a.*'))
            ->from($db->quoteName('#__churchdirectory_details', 'a'));

        $query->select(
            $db->quoteName('k.name', 'kml_name')
            . ', ' . $db->quoteName('k.style', 'kml_style')
            . ', ' . $db->quoteName('k.params', 'kml_params')
            . ', ' . $db->quoteName('k.alias', 'kml_alias')
            . ', ' . $db->quoteName('k.access', 'kml_access')
            . ', ' . $db->quoteName('k.lat', 'kml_lat')
            . ', ' . $db->quoteName('k.lng', 'kml_lng')
        );
        $query->join(
            'LEFT',
            $db->quoteName('#__churchdirectory_kml', 'k')
            . ' ON ' . $db->quoteName('k.id') . ' = ' . $db->quoteName('a.kmlid')
        );

        $query->select(
            $db->quoteName('fu.id', 'funit_id')
            . ', ' . $db->quoteName('fu.name', 'funit_name')
            . ', ' . $db->quoteName('fu.image', 'funit_image')
            . ', ' . $db->quoteName('fu.access', 'funit_access')
        );
        $query->join(
            'LEFT',
            $db->quoteName('#__churchdirectory_familyunit', 'fu')
            . ' ON ' . $db->quoteName('fu.id') . ' = ' . $db->quoteName('a.funitid')
        );

        $query->select(
            $db->quoteName('c.title', 'category_title')
            . ', ' . $db->quoteName('c.params', 'category_params')
            . ', ' . $db->quoteName('c.alias', 'category_alias')
            . ', ' . $db->quoteName('c.access', 'category_access')
        );
        $query->join(
            'INNER',
            $db->quoteName('#__categories', 'c')
            . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid')
        );

        $query->select(
            'CASE WHEN ' . $db->quoteName('a.created_by_alias') . " > ' ' THEN "
            . $db->quoteName('a.created_by_alias') . ' ELSE ' . $db->quoteName('ua.name') . ' END AS author'
        );
        $query->select($db->quoteName('ua.email', 'author_email'));
        $query->select($db->quoteName('ua.name', 'created_by'));
        $query->select($db->quoteName('uam.name', 'modified_by'));
        $query->select($db->quoteName('user.name', 'user_id'));
        $query->join(
            'LEFT',
            $db->quoteName('#__users', 'ua')
            . ' ON ' . $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by')
        );
        $query->join(
            'LEFT',
            $db->quoteName('#__users', 'uam')
            . ' ON ' . $db->quoteName('uam.id') . ' = ' . $db->quoteName('a.modified_by')
        );
        $query->join(
            'LEFT',
            $db->quoteName('#__users', 'user')
            . ' ON ' . $db->quoteName('user.id') . ' = ' . $db->quoteName('a.user_id')
        );

        // Filter by state.
        $state = $this->getState('filter.published');

        if (is_numeric($state)) {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $state);

            $nullDate = $db->quote($db->getNullDate());
            $nowDate  = $db->quote(Factory::getDate()->toSql());
            $query->where('(' . $db->quoteName('a.publish_up') . ' = ' . $nullDate . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $nowDate . ')')
                ->where('(' . $db->quoteName('a.publish_down') . ' = ' . $nullDate . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $nowDate . ')');
        }

        if ($user && $access = $this->getState('filter.access')) {
            $groups = implode(',', array_map('intval', $user->getAuthorisedViewLevels()));
            $query->where($db->quoteName('a.access') . ' IN (' . $groups . ')')
                ->where($db->quoteName('c.access') . ' IN (' . $groups . ')');
        }

        if ($mstatus = $this->getState('filter.mstatus')) {
            $query->where($db->quoteName('a.mstatus') . ' = ' . (int) $mstatus);
        }

        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' IN (' . $db->quote($language) . ', ' . $db->quote('*') . ')');
        }

        $order = $this->getState('filter.order', 'a.id');
        $query->order($db->escape((string) $order) . ' ASC');

        return $query;
    }

    /**
     * Run an export — writes directly to the output stream and exits.
     *
     * @param   string  $type    Export type (csv|kml|pdf|missingphotos).
     * @param   string  $report  File-name stem.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getExport(string $type, string $report): void
    {
        $params = ComponentHelper::getParams('com_churchdirectory');

        // Prime state via the auto-populate guard, then force the export-only
        // filter. Doing setState() before populateState() lets the request /
        // session re-supply filter.published and bypass the safety net.
        $this->getState();
        $this->setState('filter.published', 1);

        $reportBuild = new ReportbuildHelper();
        $items       = $this->getDatabase()->setQuery($this->getListQuery())->loadObjectList();

        foreach ($items as $item) {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

            $temp = new Registry();
            $temp->loadString($item->params ?? '');
            $itemParams = clone $params;
            $itemParams->merge($temp);
            $item->params = $itemParams;

            $catReg = new Registry();
            $catReg->loadString($item->category_params ?? '');
            $item->category_params = $catReg;

            if ((int) $item->params->get('dr_show_email', 0) === 1) {
                $item->email_to = trim((string) ($item->email_to ?? ''));

                if ($item->email_to !== '' && !MailHelper::isEmailAddress($item->email_to)) {
                    $item->email_to = null;
                }
            }
        }

        match ($type) {
            'csv'           => $reportBuild->getCsv($items, $report),
            'kml'           => $reportBuild->getKml($items, $report),
            'pdf'           => $reportBuild->getPdf(),
            'missingphotos' => $reportBuild->getMissingPhotos($items, $report),
            default         => null,
        };
    }
}
