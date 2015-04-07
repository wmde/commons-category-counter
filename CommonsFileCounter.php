<?php
require( 'local-config.php' );

class CommonsFileCounter {
	const INTERVAL_WEEKLY       = '+1 week';
	const INTERVAL_MONTHLY      = '+1 month';
	const INTERVAL_QUARTERLY    = '+3 month';
	const INTERVAL_SEMIANNUAL   = '+6 month';
	const INTERVAL_YEARLY       = '+12 month';

	private $_dbLink;
	private $_result;
	private $_allFiles = array();
	private $_searchedCategories = array();

	public function __construct() {
		try {
			$this->_category    = str_replace( ' ', '_', $_REQUEST['category'] );
			$this->_interval    = $this->_getIntervalString( $_REQUEST['interval'] );
			$this->_dFrom       = $_REQUEST['dFrom'];
			$this->_dUntil      = $_REQUEST['dUntil'];
		} catch( Exception $e ) {
			echo $e->getMessage();
			exit();
		}
	}

	public function run() {
		try {
			$this->_dbLink = $this->_getDbConn();
			$this->_result = $this->_createResultArray();

			$this->_getFiles( $this->_category );
			$this->_getSubCats( $this->_category );
			$this->_getFeaturedAndQualityCount();
			$this->_result['usage'] = $this->_getGlobalUsageCount();
		} catch( Exception $e ) {
			echo $e->getMessage() . "\n";
		}
	}

	public function getResult() {
		return $this->_result;
	}

	private function _getIntervalString( $interval ) {
		$constName = 'self::INTERVAL_' . strtoupper( $interval );
		if( !defined( $constName ) ) {
			throw new Exception( "The given interval parameter is not supported.\n" );
		}
		return constant( $constName );
	}

	private function _getDbConn() {
		$dbLink = mysql_connect( DB_HOST, DB_USER, DB_PASS );
		if( !$dbLink ) {
			throw new Exception( "ERROR: could not connect to database server\n" );
		}

		$dbSelected = mysql_select_db( DB_NAME, $dbLink ) or die( "ERROR: could not select database\n" );
		if( !$dbSelected ) {
			throw new Exception( "Could not select database: " . DB_NAME . "\n" );
		}

		return $dbLink;
	}

	private function _getFiles( $categoryTitle ) {
		$sql = "SELECT page_id, page_title, rev_user_text, rev_timestamp FROM categorylinks " .
			"INNER JOIN page ON cl_from = page_id " .
			"INNER JOIN revision ON rev_page = page_id " .
			"AND rev_timestamp <= '" . $this->_dUntil . "' " .
			"AND rev_parent_id = 0 " .
			"WHERE cl_type = 'file' " .
			"AND cl_to = '" . mysql_real_escape_string( $categoryTitle ) . "'";
		$result = mysql_query( $sql, $this->_dbLink );

		$resultTimestamps = array_reverse( array_keys( $this->_result['files'] ) );

		while( $row = mysql_fetch_assoc( $result ) ) {
			$dateKey = $this->_dFrom;

			if( $row['rev_timestamp'] >= $this->_dFrom ) {
				foreach( $resultTimestamps as $ts ) {
					if( $ts > $row['rev_timestamp'] ) {
						$dateKey = $ts;
					}
				}
			}

			$this->_allFiles[$row['page_id']] = $row['page_id'];
			$this->_result['files'][$dateKey][$row["page_id"]] = $row["page_id"];
			$this->_result['users'][$row["rev_user_text"]] = $row["rev_user_text"];
		}
		mysql_free_result( $result );
	}

	private function _getFeaturedAndQualityCount() {
		$sql = "SELECT cl_from, GROUP_CONCAT(cl_to SEPARATOR ' ') AS cl_to " .
			"FROM categorylinks " .
			"WHERE cl_from IN (" . implode( ', ', $this->_allFiles ) . ") " .
			"GROUP BY cl_from";
		$result = mysql_query( $sql, $this->_dbLink );
		while( $result && $row = mysql_fetch_assoc( $result ) ) {
			if( strpos( $row['cl_to'], 'Featured_' ) > -1 ) {
				$this->_result['featured'] ++;
			} elseif( strpos( $row['cl_to'], 'Quality_images_' ) > -1 ) {
				$this->_result['quality'] ++;
			}
		}
		mysql_free_result( $result );
	}

	private function _getGlobalUsageCount() {
		$sql = "SELECT COUNT(1) AS usagecount " .
			"FROM globalimagelinks " .
			"INNER JOIN page ON page_title = gil_to " .
			"WHERE page_id IN (" . implode( ", ", $this->_allFiles ) . ")";
		$result = mysql_query( $sql, $this->_dbLink );

		if( $result && $row = mysql_fetch_assoc( $result ) ) {
			return intval( $row['usagecount'] );
		}
		mysql_free_result( $result );

		return 0;
	}

	private function _getSubCats( $categoryTitle ) {
		$sql = "SELECT page_id, page_title FROM categorylinks " .
			"INNER JOIN page ON cl_from = page_id " .
			"WHERE cl_type = 'subcat' " .
			"AND cl_to = '" . mysql_real_escape_string( $categoryTitle ) . "'";
		$result = mysql_query( $sql, $this->_dbLink );

		while( $row = mysql_fetch_assoc( $result ) ) {
			if( !in_array( $row["page_id"], $this->_searchedCategories ) ) {
				$this->_searchedCategories[] = $row["page_id"];
				$this->_getFiles( $row["page_title"] );
				$this->_getSubCats( $row["page_title"] );
			}
		}

		mysql_free_result( $result );
	}

	private function _createResultArray() {
		$resultArray = array(
			'files' => array(),
			'users' => array(),
			'featured' => 0,
			'quality' => 0,
			'usage' => 0
		);

		$pointInTime = $this->_dFrom;
		while( $pointInTime < $this->_dUntil ) {
			$resultArray['files'][$pointInTime] = array();
			$pointInTime = date ( 'YmdHis' , strtotime ( $this->_interval , strtotime ( $pointInTime ) ) );
		}
		$resultArray['files'][$pointInTime] = array();
		return $resultArray;
	}
}
