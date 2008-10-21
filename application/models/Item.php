<?php
require_once('interfaces/iSearchable.php');
class Item extends Model implements iSearchable
{
	private $AWS_Access_Key='0RTAFSRZ27W8KM2JAJ02';
	private $AWS_AssociateTag='phppos-20';
	
	/*
	Determines if a given item_id is an item
	*/
	function exists($item_id)
	{
		$this->db->from('items');	
		$this->db->where('item_id',$item_id);
		$query = $this->db->get();
		
		return ($query->num_rows()==1);
	}
		
	/*
	Returns all the items
	*/
	function get_all()
	{
		$this->db->from('items');
		$this->db->order_by("name", "asc");
		return $this->db->get();		
	}
	
	/*
	Gets information about a particular item
	*/
	function get_info($item_id)
	{
		$this->db->from('items');
		$this->db->where('item_id',$item_id);
		$query = $this->db->get();
		
		if($query->num_rows()==1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $item_id is NOT an item
			$item_obj=new stdClass();
			
			//Get all the fields from items table
			$fields = $this->db->list_fields('items');
			
			foreach ($fields as $field)
			{
				$item_obj->$field='';
			}
			
			return $item_obj;
		}
	}
	
	/*
	Uses Amazon to find out pricing, name, description of item, list price....
	If amazon does not have information use UPCDatabase.com for basic information
	*/
	function find_item_info($item_number)
	{
		//Get blank info object
		$item_info = $this->get_info(-1);
		
		$request = "http://ecs.amazonaws.com/onca/xml?Service=AWSECommerceService".
		"&AWSAccessKeyId=$this->AWS_Access_Key&AssociateTag=$this->AWS_AssociateTag&SearchIndex=All".
		"&Operation=ItemLookup&ItemId=$item_number&IdType=UPC&ResponseGroup=ItemAttributes,OfferSummary";
		$session = curl_init($request); 
   		curl_setopt($session, CURLOPT_HEADER, false); 
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true); 
    	$response = curl_exec($session); 
    	curl_close($session);  
		$parsed_xml = simplexml_load_string($response);
		//UPC Search
		if($parsed_xml->Items->Request->IsValid && count($parsed_xml->Items->Request->Errors)==0)
		{
			$item_info->item_number=$item_number;
			$item_info->name=(string)$parsed_xml->Items->Item[0]->ItemAttributes->Title;
			$item_info->description=(string)$parsed_xml->Items->Item[0]->ItemAttributes->Title;
			$item_info->category=(string)$parsed_xml->Items->Item[0]->ItemAttributes->Binding;		
			$item_info->unit_price=(string)$parsed_xml->Items->Item[0]->ItemAttributes->ListPrice->FormattedPrice;
			//remove any non numberic symbols
			$item_info->unit_price=preg_replace("/[^0-9\.]/","",$item_info->unit_price);
			$item_info->tax_percent=$this->config->item('default_tax_rate');
			$item_info->url=(string)$parsed_xml->Items->Item[0]->DetailPageURL;
			$item_info->provider=$this->lang->line('items_amazon');
			return $item_info;
		}
		else
		{
			$this->xmlrpc->server('http://www.upcdatabase.com/rpc', 80);
			$this->xmlrpc->method('lookupUPC');

			$request = array($item_number);
			$this->xmlrpc->request($request);
	
			if ($this->xmlrpc->send_request())
			{
				$parsed_xml = $this->xmlrpc->display_response();
				if(is_array($parsed_xml))
				{
					$item_info->item_number=$item_number;
					$item_info->name=$parsed_xml['description'];
					$item_info->description=$parsed_xml['issuerCountry'].' - '.$parsed_xml['size'];
					$item_info->tax_percent=$this->config->item('default_tax_rate');
					$item_info->url="http://www.upcdatabase.com/item/$item_number";
					$item_info->provider=$this->lang->line('items_upc_database');						
				}
			}
		}
		
		return $item_info;
	}
	
	/*
	Gets information about multiple items
	*/
	function get_multiple_info($item_ids)
	{
		$this->db->from('items');
		$this->db->where_in('item_id',$item_ids);
		$this->db->order_by("item", "asc");
		return $this->db->get();		
	}
	
	/*
	Inserts or updates a item
	*/
	function save(&$item_data,$item_id=false)
	{		
		if (!$item_id or !$this->exists($item_id))
		{
			if($this->db->insert('items',$item_data))
			{
				$item_data['item_id']=$this->db->insert_id();
				return true;
			}
			return false;
		}
		
		$this->db->where('item_id', $item_id);
		return $this->db->update('items',$item_data);		
	}
	
	/*
	Deletes one item
	*/
	function delete($item_id)
	{
		return $this->db->delete('items', array('item_id' => $item_id)); 
	}
	
	/*
	Deletes a list of items
	*/
	function delete_list($item_ids)
	{
		$this->db->where_in('item_id',$item_ids);
		return $this->db->delete('items');		
 	}
 	
 	/*
	Get search suggestions to find items
	*/
	function get_search_suggestions($search,$limit=25)
	{
		$suggestions = array();
		
		$this->db->from('items');
		$this->db->like('name', $search); 
		$this->db->order_by("name", "asc");		
		$by_name = $this->db->get();
		foreach($by_name->result() as $row)
		{
			$suggestions[]=$row->name;		
		}
				
		//only return $limit suggestions
		if(count($suggestions > $limit))
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}
		return $suggestions;
	
	}
	
	/*
	Preform a search on items
	*/
	function search($search)
	{
		$this->db->from('items');
		$this->db->like('name', $search); 
		$this->db->order_by("name", "asc");				
		return $this->db->get();	
	}

}
?>