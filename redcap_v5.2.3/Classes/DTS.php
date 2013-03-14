<?php
class DTS
{
	private $_connection;
	
	public function __construct($conn)
	{
		global $dtsHostname, $dtsUsername, $dtsPassword, $dtsDb;
		
		if (empty($conn))
		{
			$dts_conn = mysqli_connect($dtsHostname, $dtsUsername, $dtsPassword, $dtsDb);
			if (!$dts_conn) die("Failed to connect to the dts database server");
			
			$this->_connection = $dts_conn;
		}
		else
		{
			$this->_connection = $conn;
		}
	}
	
	public function getDistinctRedcapIds($id)
	{
		$query = "SELECT DISTINCT redcap_id 
			  FROM project_recommendation 
			  WHERE redcap_project_id = $id
			  	AND recommendation_status = 'Pending'
			  ORDER BY ABS(redcap_id), redcap_id";
		return db_query($query, $this->_connection);
	}
	
	/**
	 * get the number of pending recomendations for a project
	 * 
	 * @param $id
	 * @return int count - the number pending
	 */
	public function getPendingCountByProjectId($id)
	{
		$query = "SELECT count(redcap_project_id) as count, redcap_project_id
				  FROM project_recommendation
				  WHERE redcap_project_id = $id
			  		AND recommendation_status = 'Pending'
				  GROUP by redcap_project_id";
		$count = db_result(db_query($query, $this->_connection), 0, 0);
		return $count;
	}
	
	public function findPendingByRedcapId($project_id,$redcap_id)
	{
		global $longitudinal;
		
		if ($longitudinal)
		{
			$query = "SELECT *
					  FROM project_recommendation
					  WHERE redcap_project_id = $project_id
					  	AND redcap_id = '$redcap_id'
					  	AND recommendation_status = 'Pending'
					  ORDER BY event_id, target_field, date_of_service";
		}
		else
		{
			$query = "SELECT * 
					  FROM project_recommendation
					  WHERE redcap_project_id = $project_id
					  	AND redcap_id = '$redcap_id'
					  	AND recommendation_status = 'Pending'
					  ORDER BY target_field, date_of_service";
		}
		return db_query($query, $this->_connection);
	}
	
	public function getTransferRecommendationStatuses()
	{
		$query = "SELECT * FROM transfer_recommendation_status";
		return db_query($query, $this->_connection);
	}
}

