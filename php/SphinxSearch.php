<?php
require_once 'sphinxapi.php';
class SphinxSearch
{
	private $idx_name = "dist1";
	private $result = array();
	private $host = "127.0.0.1";
	private $port = 9310;
	private $minid = 0;
	private $maxid = 0;
	private $offset = 0;
	private $limit = 1000;
	private $btime = 0;
	private $etime = 0;
	private $cl = null;
	
	public function __construct()
	{
		$this->cl = new SphinxClient();
	}
	public function Search( $condition )
	{
		if( !is_array($condition) )
		{
			return null;
		}
		if( !$this->cl )
		{
			$this->cl = new SphinxClient();
		}
		if( $condition['host'] != '' )
		{
			$this->host = $condition['host'];
		}
		if( $condition['port'] != '' )
		{
			$this->port = $condition['port'];
		}
		if( $condition['minid'] != '' )
		{
			$this->minid = $condition['minid'];
		}
		if( $condition['maxid'] != '' )
		{
			$this->maxid = $condition['maxid'];
		}
		if( $condition['offset'] != '' )
		{
			$this->offset = $condition['offset'];
		}
		if( $condition['limit'] != '' )
		{
			$this->limit = $condition['limit'];
		}
		if( $condition['btime'] != '' )
		{
			$this->btime = $condition['btime'];
		}
		if( $condition['etime'] != '' )
		{
			$this->etime = $condition['etime'];
		}
		
		$this->cl->SetServer( $this->host, $this->port);
		$this->cl->SetConnectTimeout ( 1 );
		$this->cl->SetArrayResult ( true );
		//根据noIDRange变量决定是否执行SetIDRange，noIDRange被定义的话就不执行SetIDRange
		if( !isset( $condition['noIDRange'] ) )
		{
		  $this->cl->SetIDRange($this->minid, $this->maxid);
		}
		//匹配模式
		if( isset( $condition['matchMode'] ) ){
			$this->cl->SetMatchMode($condition['matchMode']) ;
		}else{
			$this->cl->SetMatchMode(SPH_MATCH_EXTENDED); //ʹ�ö��ֶ�ģʽ
		}
		
		
// 		echo $this->limit;exit;
// 		$this->cl->SetLimits($this->offset, $this->limit, $this->limit);
		
		if($condition['limit']){
			$this->cl->SetLimits($condition['offset'],$condition['limit'], 1000);
		}else{
			$this->cl->SetLimits($this->offset,$this->limit, 1000);
		}
		
		if( $this->btime < $this->etime )
		{
			$this->cl->SetFilterRange("ci_content_time", $this->btime , $this->etime);
		}
		if( is_array($condition['filter'] ))
		{
			foreach ($condition['filter'] as $key => $value )
			{
				if( is_array($value) )
				{
					$this->cl->SetFilter($key, $value);
				}
				else 
				{
					$this->cl->SetFilter($key, array($value));
				}
			}
		}
		if( is_array($condition['filterexclude'] ))
		{
			foreach ($condition['filterexclude'] as $key => $value )
			{
				if( is_array($value) )
				{
					$this->cl->SetFilter($key, $value, true);
				}
				else 
				{
					$this->cl->SetFilter($key, array($value), true);
				}
			}
		}
		if($condition['orderby']){
			
			$this->cl->SetSortMode(SPH_SORT_EXTENDED, $condition['orderby']);
		}
        $res = $this->cl->Query($condition['keyword'], $this->idx_name);
		if ( $res===false )
		{
			print "Query failed: " . $this->cl->GetLastError() . ".\n";
		} 
		else
		{
            if ( $this->cl->GetLastWarning() )
				print "WARNING: " . $this->cl->GetLastWarning() . "\n\n";
			if ( is_array($res["matches"]) )
			{
				$this->result['total']=$res['total'];
				foreach ( $res["matches"] as $docinfo )
				{
					$cid = $docinfo[id];
					$res_tmp = $docinfo["attrs"];
					$res_tmp["ci_id"] = $cid;
					$res_tmp["id"] = $cid;
					$res_tmp['ci_content_time'] = intval($res_tmp['ci_content_time']);
					$res_tmp['ci_ctime'] = intval($res_tmp['ci_ctime']);
					$this->result[] = $res_tmp;
				}
			}
		}
		return $this->result;
	}
	
	public function updateAttr ( $attrs, $values, $mva=false )
	{
		return $this->cl->UpdateAttributes( $this->idx_name, $attrs, $values, $mva );
	}
	
	/**
	 * @return the $idx_name
	 */
	public function getIdx_name()
	{
		return $this->idx_name;
	}

	/**
	 * @return the $host
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @return the $port
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @return the $minid
	 */
	public function getMinid()
	{
		return $this->minid;
	}

	/**
	 * @return the $maxid
	 */
	public function getMaxid()
	{
		return $this->maxid;
	}

	/**
	 * @return the $offset
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @return the $limit
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * @return the $btime
	 */
	public function getBtime()
	{
		return $this->btime;
	}

	/**
	 * @return the $etime
	 */
	public function getEtime()
	{
		return $this->etime;
	}

	/**
	 * @param field_type $idx_name
	 */
	public function setIdx_name( $idx_name )
	{
		$this->idx_name = $idx_name;
	}

	/**
	 * @param field_type $host
	 */
	public function setHost( $host )
	{
		$this->host = $host;
	}

	/**
	 * @param field_type $port
	 */
	public function setPort( $port )
	{
		$this->port = $port;
	}

	/**
	 * @param field_type $minid
	 */
	public function setMinid( $minid )
	{
		$this->minid = $minid;
	}

	/**
	 * @param field_type $maxid
	 */
	public function setMaxid( $maxid )
	{
		$this->maxid = $maxid;
	}

	/**
	 * @param field_type $offset
	 */
	public function setOffset( $offset )
	{
		$this->offset = $offset;
	}

	/**
	 * @param field_type $limit
	 */
	public function setLimit( $limit )
	{
		$this->limit = $limit;
	}

	/**
	 * @param field_type $btime
	 */
	public function setBtime( $btime )
	{
		$this->btime = $btime;
	}

	/**
	 * @param field_type $etime
	 */
	public function setEtime( $etime )
	{
		$this->etime = $etime;
	}

	
}