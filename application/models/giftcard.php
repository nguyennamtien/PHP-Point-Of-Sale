<?php
class Giftcard extends Model
{
	/*
	Determines if a given giftcard_id is an giftcard
	*/
	function exists($giftcard_id)
	{
		$this->db->from('giftcards');
		$this->db->where('giftcard_id',$giftcard_id);
		$this->db->where('deleted',0);
		$query = $this->db->get();

		return ($query->num_rows()==1);
	}

	/*
	Returns all the giftcards
	*/
	function get_all()
	{
		$this->db->from('giftcards');
		$this->db->where('deleted',0);
		$this->db->order_by("giftcard_number", "asc");
		return $this->db->get();
	}

	function get_all_filtered()
	{
		$this->db->from('giftcards');
		/*
		if ($low_inventory !=0 )
		{
			$this->db->where('quantity <=','reorder_level');
		}
		if ($is_serialized !=0 )
		{
			$this->db->where('is_serialized',1);
		}
		if ($no_description!=0 )
		{
			$this->db->where('description','');
		}
		*/
		$this->db->where('deleted',0);
		$this->db->order_by("giftcard_number", "asc");
		return $this->db->get();
	}

	/*
	Gets information about a particular giftcard
	*/
	function get_info($giftcard_id)
	{
		$this->db->from('giftcards');
		$this->db->where('giftcard_id',$giftcard_id);
		$this->db->where('deleted',0);
		
		$query = $this->db->get();

		if($query->num_rows()==1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $giftcard_id is NOT an giftcard
			$giftcard_obj=new stdClass();

			//Get all the fields from giftcards table
			$fields = $this->db->list_fields('giftcards');

			foreach ($fields as $field)
			{
				$giftcard_obj->$field='';
			}

			return $giftcard_obj;
		}
	}

	/*
	Get an giftcard id given an giftcard number
	*/
	function get_giftcard_id($giftcard_number)
	{
		$this->db->from('giftcards');
		$this->db->where('giftcard_number',$giftcard_number);
		$this->db->where('deleted',0);

		$query = $this->db->get();

		if($query->num_rows()==1)
		{
			return $query->row()->giftcard_id;
		}

		return false;
	}

	/*
	Gets information about multiple giftcards
	*/
	function get_multiple_info($giftcard_ids)
	{
		$this->db->from('giftcards');
		$this->db->where_in('giftcard_id',$giftcard_ids);
		$this->db->where('deleted',0);
		$this->db->order_by("giftcard_number", "asc");
		return $this->db->get();
	}

	/*
	Inserts or updates a giftcard
	*/
	function save(&$giftcard_data,$giftcard_id=false)
	{
		if (!$giftcard_id or !$this->exists($giftcard_id))
		{
			if($this->db->insert('giftcards',$giftcard_data))
			{
				$giftcard_data['giftcard_id']=$this->db->insert_id();
				return true;
			}
			return false;
		}

		$this->db->where('giftcard_id', $giftcard_id);
		return $this->db->update('giftcards',$giftcard_data);
	}

	/*
	Updates multiple giftcards at once
	*/
	function update_multiple($giftcard_data,$giftcard_ids)
	{
		$this->db->where_in('giftcard_id',$giftcard_ids);
		return $this->db->update('giftcards',$giftcard_data);
	}

	/*
	Deletes one giftcard
	*/
	function delete($giftcard_id)
	{
		$this->db->where('giftcard_id', $giftcard_id);
		return $this->db->update('giftcards', array('deleted' => 1));
	}

	/*
	Deletes a list of giftcards
	*/
	function delete_list($giftcard_ids)
	{
		$this->db->where_in('giftcard_id',$giftcard_ids);
		return $this->db->update('giftcards', array('deleted' => 1));
 	}

 	/*
	Get search suggestions to find giftcards
	*/
	function get_search_suggestions($search,$limit=25)
	{
		$suggestions = array();

		$this->db->from('giftcards');
		$this->db->like('giftcard_number', $search);
		$this->db->where('deleted',0);
		$this->db->order_by("giftcard_number", "asc");
		$by_number = $this->db->get();
		foreach($by_number->result() as $row)
		{
			$suggestions[]=$row->giftcard_number;
		}

		$this->db->select('giftcard_number');
		$this->db->from('giftcards');
		$this->db->where('deleted',0);
		$this->db->distinct();
		$this->db->like('giftcard_number', $search);
		$this->db->order_by("giftcard_number", "asc");
		$by_category = $this->db->get();
		foreach($by_category->result() as $row)
		{
			$suggestions[]=$row->giftcard_number;
		}

		$this->db->from('giftcards');
		$this->db->like('giftcard_number', $search);
		$this->db->where('deleted',0);
		$this->db->order_by("giftcard_number", "asc");
		$by_giftcard_number = $this->db->get();
		foreach($by_giftcard_number->result() as $row)
		{
			$suggestions[]=$row->giftcard_number;
		}


		//only return $limit suggestions
		if(count($suggestions > $limit))
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}
		return $suggestions;

	}

	function get_giftcard_search_suggestions($search,$limit=25)
	{
		$suggestions = array();

		$this->db->from('giftcards');
		$this->db->where('deleted',0);
		$this->db->like('giftcard_number', $search);
		$this->db->order_by("giftcard_number", "asc");
		$by_number = $this->db->get();
		foreach($by_number->result() as $row)
		{
			$suggestions[]=$row->giftcard_id.'|'.$row->giftcard_number;
		}

		$this->db->from('giftcards');
		$this->db->where('deleted',0);
		$this->db->like('giftcard_number', $search);
		$this->db->order_by("giftcard_number", "asc");
		$by_giftcard_number = $this->db->get();
		foreach($by_giftcard_number->result() as $row)
		{
			$suggestions[]=$row->giftcard_id.'|'.$row->giftcard_number;
		}

		//only return $limit suggestions
		if(count($suggestions > $limit))
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}
		return $suggestions;

	}

	function get_category_suggestions($search)
	{
		$suggestions = array();
		$this->db->distinct();
		$this->db->select('giftcard_number');
		$this->db->from('giftcards');
		$this->db->like('giftcard_number', $search);
		$this->db->where('deleted', 0);
		$this->db->order_by("giftcard_number", "asc");
		$by_category = $this->db->get();
		foreach($by_category->result() as $row)
		{
			$suggestions[]=$row->category;
		}

		return $suggestions;
	}

	/*
	Preform a search on giftcards
	*/
	function search($search)
	{
		$this->db->from('giftcards');
		$this->db->where("(giftcard_number LIKE '%".$this->db->escape_like_str($search)."%' or 
		giftcard_number LIKE '%".$this->db->escape_like_str($search)."%' or 
		giftcard_number LIKE '%".$this->db->escape_like_str($search)."%') and deleted=0");
		$this->db->order_by("giftcard_number", "asc");
		return $this->db->get();	
	}

	function get_categories()
	{
		$this->db->select('giftcard_number');
		$this->db->from('giftcards');
		$this->db->where('deleted',0);
		$this->db->distinct();
		$this->db->order_by("giftcard_number", "asc");

		return $this->db->get();
	}
	
	function update( $giftcard_number, $value )
	{
		//Update giftcard value
		$this->db->query('UPDATE '.$this->db->dbprefix('giftcards').' SET `value`='.$value.' WHERE `giftcard_number`='.$giftcard_number );
	}
}
?>
