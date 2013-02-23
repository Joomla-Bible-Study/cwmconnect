<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * For Getting GeoUpdate from Google
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelGeoUpdate extends JModelLegacy
{
	/**
	 * Set start Time
	 *
	 * @var float The time the process started
	 */
	private $_startTime = null;

	/** @var array The members to process */
	private $_membersStack = array();

	/** @var int Total numbers of members in this site */
	public $totalMembers = 0;

	/** @var int Numbers of members already processed */
	public $doneMembers = 0;

	/** @var int Numbers of members already processed */
	private $_memberID = null;

	/**
	 * Returns the current timestampt in decimal seconds
	 *
	 * @return string
	 */
	private function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());

		return ((float) $usec + (float) $sec);
	}

	/**
	 * Starts or resets the internal timer
	 *
	 * @return void
	 */
	private function resetTimer()
	{
		$this->_startTime = $this->microtime_float();
	}

	/**
	 * Makes sure that no more than 3 seconds since the start of the timer have elapsed
	 *
	 * @return bool
	 */
	private function haveEnoughTime()
	{
		$now     = $this->microtime_float();
		$elapsed = abs($now - $this->_startTime);

		return $elapsed < 3;
	}

	/**
	 * Saves the file/folder stack in the session
	 *
	 * @return void
	 */
	private function saveStack()
	{
		$stack = array(
			'members' => $this->_membersStack,
			'total'   => $this->totalMembers,
			'done'    => $this->doneMembers
		);
		$stack = json_encode($stack);

		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			if (function_exists('gzdeflate') && function_exists('gzinflate'))
			{
				$stack = gzdeflate($stack, 9);
			}
			$stack = base64_encode($stack);
		}
		$session = JFactory::getSession();
		$session->set('geoupdate_stack', $stack, 'churchdirectory');
	}

	/**
	 * Resets the file/folder stack saved in the session
	 *
	 * @return void
	 */
	private function resetStack()
	{
		$session = JFactory::getSession();
		$session->set('geoupdate_stack', '', 'churchdirectory');
		$this->_membersStack = array();
		$this->totalMembers  = 0;
		$this->doneMembers   = 0;
	}

	/**
	 * Loads the file/folder stack from the session
	 *
	 * @return void
	 */
	private function loadStack()
	{
		$session = JFactory::getSession();
		$stack   = $session->get('geoupdate_stack', '', 'churchdirectory');

		if (empty($stack))
		{
			$this->_membersStack = array();
			$this->totalMembers  = 0;
			$this->doneMembers   = 0;

			return;
		}

		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			$stack = base64_decode($stack);

			if (function_exists('gzdeflate') && function_exists('gzinflate'))
			{
				$stack = gzinflate($stack);
			}
		}
		$stack = json_decode($stack, true);

		$this->_membersStack = $stack['members'];
		$this->totalMembers  = $stack['total'];
		$this->doneMembers   = $stack['done'];
	}

	/**
	 * The $id of the Member to retrieve date from.
	 *
	 * @param   string  $id  The id of the member to update
	 *
	 * @return void
	 */
	private function getMembers($id = null)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id, name, address, suburb, state, postcode, lat, lng , country');
		$query->from($db->qn('#__churchdirectory_details'));
		if ($id)
			$query->where('id = ' . $db->q($id));
		$db->setQuery($query);
		$members = $db->loadObjectList();

		if (empty($members))
		{
			$members = array();
		}

		$this->_membersStack = array_merge($this->_membersStack, $members);

		$this->totalMembers += count($members);
	}

	/**
	 *  Run the Update will there is time.
	 *
	 * @param   bool         $resetTimer  If the time must be reset
	 * @param   string|null  $id          Record ID to update
	 *
	 * @return bool
	 */
	public function run($resetTimer = true, $id = null)
	{
		if ($resetTimer)
		{
			$this->resetTimer();
		}

		$this->loadStack();
		$result = true;

		while ($result && $this->haveEnoughTime())
		{
			$result = $this->RealRun($id);
		}

		$this->saveStack();

		return $result;
	}

	/**
	 * Start the Run through the members or member.
	 *
	 * @param   string|null  $id  ID of a member if only updating a single one.
	 *
	 * @return bool
	 */
	private function RealRun($id = null)
	{
		if (!empty($this->_membersStack))
		{
			while (!empty($this->_membersStack) && $this->haveEnoughTime())
			{
				$file = array_pop($this->_membersStack);
				$this->doneMembers++;
				$this->update($file, $id);
			}
		}

		if (empty($this->_membersStack))
		{
			// Just finished
			$this->resetStack();

			return false;
		}

		// If we have more folders or files, continue in the next step
		return true;
	}

	/**
	 * Start Looking though the members
	 *
	 * @param   string|null  $id  Run the ID if needed.
	 *
	 * @return bool
	 */
	public function startScanning($id = null)
	{
		$this->resetStack();
		$this->resetTimer();
		$this->getMembers();

		$id = JFactory::getApplication()->input->getInt('id');

		if (empty($this->_membersStack))
		{
			$this->_membersStack = array();
		}
		asort($this->_membersStack);

		$this->saveStack();

		if (!$this->haveEnoughTime())
		{
			return true;
		}
		else
		{
			return $this->run(false, $id);
		}
	}

	/**
	 * Update Lng & Lat of Member
	 *
	 * @param   object  $row  Row to look through
	 * @param   string  $id   ID of member to update
	 *
	 * @return boolean
	 */
	private function update($row = null, $id = null)
	{

		$geocode_pending = false;
		$db              = $this->getDbo();

		if ($row or $id)
		{
			if ($id)
			{
				// Set Member ID for Latter Use
				$this->_memberID = $id;

				$query = $db->getQuery(true);
				$query->select('*')->from('#__churchdirectory_details')->where('id =' . $db->q($id));
				$db->setQuery($query);
				$row = $db->loadObject();

				if (is_object($row))
				{
					$row = get_object_vars($row);
				}
			}
			else
			{
				// Set Member ID for Latter Use
				$this->_memberID = $row['id'];
			}
			$base_url = "http://maps.googleapis.com/maps/api/geocode/xml?address=";

			// Initialize delay in geocode speed
			$delay           = 0;
			$geocode_pending = true;

			while ($geocode_pending)
			{
				// Defining of Rows to look up
				$address     = str_replace(' ', '+', $row['address']);
				$request_url = $base_url . $address . ",+" . str_replace(' ', '+', $row['suburb']) .
					",+" . $row['state'] . '&sensor=true';
				$xml = simplexml_load_file($request_url) or die("url not loading");

				$status = $xml->status;

				if ($status == "OK" && $xml->result->type['0'] == 'street_address')
				{
					// Successful geocode
					$geocode_pending = false;

					foreach ($xml->result AS $data):
						$ulat  = $data->geometry->location->lat;
						$ulong = $data->geometry->location->lng;

						// Create a new query object.
						$query = $db->getQuery(true);
						$query->update("#__churchdirectory_details")
							->set("lat = " . $db->q($ulat) . ", lng = " . $db->q($ulong))
							->where("id = " . $db->q($row['id']));
						$db->setQuery($query);
						$db->execute();

						// Check to see if record is int GeoErrors
						$query = $db->getQuery(true);
						$query->select('member_id')->from('#__churchdirectory_geoupdate');
						$query->where('member_id = ' . $this->_memberID);
						$db->setQuery($query);

						// If member found remove record
						if ($db->loadResult())
						{
							$query = $db->getQuery(true);
							$query->delete('#__churchdirectory_geoupdate');
							$query->where('member_id = ' . $this->_memberID);
							$db->setQuery($query);
							$db->execute();
						}
					endforeach;
				}
				elseif ($status == "OVER_QUERY_LIMIT")
				{
					// Sent geocodes too fast
					$delay += 100000;
				}
				else
				{
					// Failure to geocode
					$geocode_pending = false;

					// Create a new query object.
					$query = $db->getQuery(true);
					$query->select($db->q('*'))
						->from($db->qn('#__churchdirectory_geoupdate'))
						->where('`member_id` = ' . $db->q($row['id']));
					$db->setQuery($query);
					$info = 'Status: ' . $status . '<br /><div style="float:left; padding:5px;">' .
						'Type:</div><div style="float:left; padding:5px;">' . $xml->result->type['0'] .
						'<br />' . $xml->result->type['1'] . '</div>';

					if ($db->loadResult())
					{
						$query = $db->getQuery(true);
						$query->update("#__churchdirectory_geoupdate")
							->set('member_id = ' . $db->q($row['id']))
							->set('`status` = ' . $db->q($info));
						$db->setQuery($query);
						$db->execute();
					}
					else
					{
						$query = $db->getQuery(true);
						$query->insert("#__churchdirectory_geoupdate")
							->set('member_id = ' . $db->q($row['id']))
							->set('`status` = ' . $db->q($info));
						$db->setQuery($query);
						$db->execute();
					}
				}
				usleep($delay);
			}
		}

		return $geocode_pending;

	}

}
