<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
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
	 * @since    1.7.0
	 */
	private $startTime = null;

	/**
	 * @var array The members to process
	 * @since    1.7.0
	 */
	private $membersStack = [];

	/**
	 * @var int Total numbers of members in this site
	 * @since    1.7.0
	 */
	public $totalMembers = 0;

	/**
	 * @var int Numbers of members already processed
	 * @since    1.7.0
	 */
	public $doneMembers = 0;

	/**
	 * Returns the current time stampt in decimal seconds
	 *
	 * @return string
	 *
	 * @since    1.7.0
	 */
	private function microtime_float()
	{
		[$usec, $sec] = explode(" ", microtime());

		return ((float) $usec + (float) $sec);
	}

	/**
	 * Starts or resets the internal timer
	 *
	 * @return void
	 *
	 * @since    1.7.0
	 */
	private function resetTimer()
	{
		$this->startTime = $this->microtime_float();
	}

	/**
	 * Makes sure that no more than 3 seconds since the start of the timer have elapsed
	 *
	 * @return bool
	 *
	 * @since    1.7.0
	 */
	private function haveEnoughTime()
	{
		$now     = $this->microtime_float();
		$elapsed = abs($now - $this->startTime);

		return $elapsed < 2;
	}

	/**
	 * Saves the file/folder stack in the session
	 *
	 * @return void
	 *
	 * @throws \JsonException
	 * @since    1.7.0
	 */
	private function saveStack()
	{
		$stack = [
			'members' => $this->membersStack,
			'total'   => $this->totalMembers,
			'done'    => $this->doneMembers
		];
		$stack = json_encode($stack, JSON_THROW_ON_ERROR);

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
	 *
	 * @since    1.7.0
	 */
	private function resetStack()
	{
		$session = JFactory::getSession();
		$session->set('geoupdate_stack', '', 'churchdirectory');
		$this->membersStack = [];
		$this->totalMembers = 0;
		$this->doneMembers  = 0;
	}

	/**
	 * Loads the file/folder stack from the session
	 *
	 * @return void
	 *
	 * @throws \JsonException
	 * @since    1.7.0
	 */
	private function loadStack()
	{
		$session = JFactory::getSession();
		$stack   = $session->get('geoupdate_stack', '', 'churchdirectory');

		if (empty($stack))
		{
			$this->membersStack = [];
			$this->totalMembers = 0;
			$this->doneMembers  = 0;

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

		$stack = json_decode($stack, true, 512, JSON_THROW_ON_ERROR);

		$this->membersStack = $stack['members'];
		$this->totalMembers = $stack['total'];
		$this->doneMembers  = $stack['done'];
	}

	/**
	 * The $id of the Member to retrieve date from.
	 *
	 * @param   string  $id  The id of the member to update
	 *
	 * @return void
	 *
	 * @since    1.7.0
	 */
	private function getMembers($id = null)
	{
		$query = $this->_db->getQuery(true);
		$query->select('id, name, address, suburb, state, postcode, lat, lng , country');
		$query->from('#__churchdirectory_details');

		if ($id)
		{
			$query->where('id = ' . $this->_db->q($id));
		}

		$this->_db->setQuery($query);
		$members = $this->_db->loadObjectList();

		$this->membersStack = array_merge($this->membersStack, (array) $members);

		$this->totalMembers += count($members);
	}

	/**
	 *  Run the Update will there is time.
	 *
	 * @param   bool         $resetTimer  If the time must be reset
	 * @param   string|null  $id          Record ID to update
	 *
	 * @return bool
	 *
	 * @throws \JsonException
	 * @since    1.7.0
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
	 *
	 * @since    1.7.0
	 */
	private function RealRun($id = null)
	{
		if ($id)
		{
			$this->resetStack();
			$this->getMembers($id);
		}

		if (!empty($this->membersStack))
		{
			while (!empty($this->membersStack) && $this->haveEnoughTime())
			{
				$member = array_pop($this->membersStack);
				$this->doneMembers++;
				$this->update($member, $id);
			}
		}

		if (empty($this->membersStack))
		{
			// Just finished
			$this->resetStack();

			return false;
		}

		// If we have more Members, continue in the next step
		return true;
	}

	/**
	 * Start Looking though the members
	 *
	 * @param   int  $id  Id of member to prosses if needed.
	 *
	 * @return bool
	 *
	 * @since    1.7.0
	 */
	public function startScanning($id = null)
	{
		$this->resetStack();
		$this->resetTimer();
		$this->getMembers();

		if (!$id)
		{
			$id = JFactory::getApplication()->input->getInt('id', 0);
		}

		if (empty($this->membersStack))
		{
			$this->membersStack = [];
		}

		asort($this->membersStack);

		$this->saveStack();

		if (!$this->haveEnoughTime())
		{
			return true;
		}

		return $this->run(false, $id);
	}

	/**
	 * Update Lng & Lat of Member
	 *
	 * @param   object  $row  Row to look through
	 * @param   string  $id   ID of member to update
	 *
	 * @return boolean
	 *
	 * @since    1.7.0
	 */
	private function update($row = null, $id = null)
	{
		$key = JComponentHelper::getParams('com_churchdirectory')->get('apikey');
		$geocode_pending = false;

		if ($row || $id)
		{
			if ($id)
			{
				// Set Member ID for Latter Use
				$memberID = $id;

				$query = $this->_db->getQuery(true);
				$query->select('*')
					->from('#__churchdirectory_details')
					->where('id =' . $this->_db->q($id));
				$this->_db->setQuery($query);
				$row = $this->_db->loadObject();

				if (is_object($row))
				{
					$row = get_object_vars($row);
				}
			}
			else
			{
				// Set Member ID for Latter Use
				$memberID = $row['id'];
			}

			$base_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=";

			// Initialize delay in geocode speed
			$delay           = 0;
			$geocode_pending = true;

			while ($geocode_pending)
			{
				// Defining of Rows to look up
				$address     = str_replace(' ', '+', $row['address']);
				if (!empty($row['address']))
				{
					$request_url = $base_url . $address . ",+" . str_replace(' ', '+', $row['suburb']) .
						",+" . $row['state'] . '&sensor=true&key=' . $key;

					/** @var object $xml */
					$xml = simplexml_load_string(file_get_contents($request_url));

					if ($xml)
					{
						$status = $xml->status;
					}
					else
					{
						return true;
					}
					
					if ($status !=="OK")
					{
						var_dump($request_url);
						var_dump($status);
						var_dump($xml->result);
					}

					if ($status === "OK")
					{
						// Successful geocode
						$geocode_pending = false;

						foreach ($xml->result as $data)
						{
							$ulat  = $data->geometry->location->lat;
							$ulong = $data->geometry->location->lng;

							// Create a new query object.
							$query = $this->_db->getQuery(true);
							$query->update("#__churchdirectory_details")
								->set("lat = " . $this->_db->q($ulat) . ", lng = " . $this->_db->q($ulong))
								->where("id = " . $this->_db->q($row['id']));
							$this->_db->setQuery($query);
							$this->_db->execute();

							// Check to see if record is int GeoErrors
							$query = $this->_db->getQuery(true);
							$query->select('member_id')->from('#__churchdirectory_geoupdate');
							$query->where('member_id = ' . $memberID);
							$this->_db->setQuery($query);

							// If member found remove record
							if ($this->_db->loadResult())
							{
								$query = $this->_db->getQuery(true);
								$query->delete('#__churchdirectory_geoupdate');
								$query->where('member_id = ' . $memberID);
								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
					}
					elseif ($status === "OVER_QUERY_LIMIT")
					{
						// Sent geocodes too fast
						$delay += 100000;
					}
					else
					{
						// Failure to geocode
						$geocode_pending = false;

						// Create a new query object.
						$query = $this->_db->getQuery(true);
						$query->select('*')
							->from($this->_db->qn('#__churchdirectory_geoupdate'))
							->where('`member_id` = ' . $this->_db->q($row['id']));
						$this->_db->setQuery($query);
						$info = 'Status: ' . $status . '<br /><div style="float:left; padding:5px;">' .
							'Error Message:</div><div style="float:left; padding:5px;">' .
							$xml->result->error_message[0] . '</div>';

						if ($this->_db->loadResult())
						{
							$query = $this->_db->getQuery(true);
							$query->update("#__churchdirectory_geoupdate")
								->set('status = ' . $this->_db->q($info))
								->where('member_id = ' . $this->_db->q($row['id']));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
						else
						{
							$query = $this->_db->getQuery(true);
							$query->insert("#__churchdirectory_geoupdate")
								->set('member_id = ' . $this->_db->q($row['id']))
								->set('`status` = ' . $this->_db->q($info));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}
				}
				else
				{
					// Nothing to process.
					$geocode_pending = false;
				}
				usleep($delay);
			}
		}

		return $geocode_pending;
	}
}
