<?php

class Event
{
	public static function getEventsByProject($projectId)
	{
		$eventList = array();
		
		$sql = "SELECT * 
				FROM redcap_events_metadata rem 
					JOIN redcap_events_arms rea ON rem.arm_id = rea.arm_id
				WHERE project_id = $projectId";
		$events = db_query($sql);
		
		while ($row = db_fetch_array($events))
		{
			$eventList[$row['event_id']] = $row['descrip'];
		}
		
		return $eventList;
	}
	
	public static function getEventIdByName($projectId, $name)
	{
		$idList = getEventIdByKey($projectId, array($name));
		$id = (count($idList) > 0) ? $idList[0] : 0;
		
		return $id;
	}
	
	public static function getUniqueKeys($projectId)
	{
		$sql = "SELECT event_id, day_offset, descrip, rea.arm_id, arm_num
				FROM redcap_events_metadata rem
					JOIN redcap_events_arms rea ON rem.arm_id = rea.arm_id
				WHERE project_id = $projectId
				ORDER BY arm_id, event_id";
		$result = db_query($sql);
		
		$events = array();
		$uniqueKeys = array();
		while ($row = db_fetch_array($result))
		{
			$text = preg_replace("/[^0-9a-z_ ]/i", '', trim(label_decode($row['descrip'])));
			$text = strtolower(substr(str_replace(" ", "_", $text), 0, 18));
			if (substr($text, -1, 1) == "_") $text = substr($text, 0, -1);
			$text .= '_arm_' . $row['arm_num'];
			
			$count = count(array_keys($events, $text));
			$new = ($count > 0) ? $new = chr(97+$count) : '';
			
			$events[] = $text;
			$uniqueKeys[$row['event_id']] = $text . $new;
		}
		
		return $uniqueKeys;
	}
	
	public static function getUniqueKeysOrdered($projectId)
	{
		$uniqueKeys = Event::getUniqueKeys($projectId);
		
		$sql = "SELECT event_id, day_offset, descrip, rea.arm_id, arm_num
				FROM redcap_events_metadata rem
					JOIN redcap_events_arms rea ON rem.arm_id = rea.arm_id
				WHERE project_id = $projectId
				ORDER BY arm_num, day_offset, descrip";
		$result = db_query($sql);
		
		$keys = array();
		while ($row = db_fetch_array($result))
		{
			$keys[] = $uniqueKeys[$row['event_id']];
		}
		
		return $keys;
	}
	
	public static function getEventNameById($projectId, $id)
	{
		$uniqueKeys = array_flip(Event::getUniqueKeys($projectId));
		
		$name = array_search($id, $uniqueKeys);
		
		return $name;
	}
	
	public static function getEventIdByKey($projectId, $keys)
	{
		$uniqueKeys = Event::getUniqueKeys($projectId);
		$idList = array();
		
		foreach($keys as $key)
		{
			$idList[] = array_search($key, $uniqueKeys);
		}
		
		return $idList;
	}
}
