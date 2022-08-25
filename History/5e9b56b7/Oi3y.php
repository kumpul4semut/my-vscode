<?php defined('BASEPATH') or exit('No direct script access allowed');

class M_activity extends CI_Model
{

	public $table = 'activity';

	public function insert($data, $is_batch = false)
	{
		$is_batch ? $this->db->insert_batch($this->table, $data) : $this->db->insert($this->table, $data);
		$id = $this->db->insert_id();
		return isset($id) ? $id : false;
	}

	// ------------------------------------------------------------------------

	/**
	 * get
	 *
	 * @return void
	 */
	public function get($select = NULL, $debug = FALSE)
	{
		if (!empty($select)) {
			$this->db->select($select);
			$this->db->from($this->table);
			$q = $this->db->get();
			if (!$q || $debug) {
				return die($this->db->last_query());
			}
			return $q;
		}
		$q = $this->db->get($this->table);
		if (!$q) {
			return die($this->db->last_query());
		}
		return $q;
	}

	// ------------------------------------------------------------------------

	/**
	 * where
	 *
	 * @param  array $data
	 * @return void
	 */
	public function where(array $data)
	{
		foreach ($data as $k => $d) {
			$this->db->where($k, $d);
		}
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * where
	 *
	 * @param  array $data
	 * @return void
	 */
	public function or_where(array $data)
	{
		foreach ($data as $k => $d) {
			$this->db->or_where($k, $d);
		}
		return $this;
	}


	// ------------------------------------------------------------------------

	/**
	 * update
	 *
	 * @param  mixed $data
	 * @return void
	 */
	public function update($data)
	{
		return $this->db->update($this->table, $data);
	}

	// ------------------------------------------------------------------------

	/**
	 * delete
	 *
	 * @return void
	 */
	public function delete()
	{
		return $this->db->delete($this->table);
	}

	// ------------------------------------------------------------------------

	/**
	 * length
	 *
	 * @param  mixed $length
	 * @return void
	 */
	public function length($length)
	{
		$this->db->limit($length);
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * start
	 *
	 * @param  mixed $start
	 * @return void
	 */
	public function start($start)
	{
		$this->db->offset($start);
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * with_user
	 *
	 * join has one to table users
	 * @return void
	 */
	public function with_user()
	{
		$this->db->join('users u1', "{$this->table}.id_by_user = u1.id", 'right');
		$this->db->join('users u2', "{$this->table}.id_to_user = u2.id", 'right');
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * with_activity_type
	 *
	 * join has one to table activity_type
	 * @return void
	 */
	public function with_activity_type()
	{
		$this->db->join('activity_type at', '' . $this->table . '.id_activity_type = at.id', 'left');
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * order
	 *
	 * @param  mixed $by
	 * @param  mixed $type
	 * @return void
	 */
	public function order($by, $type)
	{
		$this->db->order_by($by, $type);
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * group
	 *
	 * @param string $group
	 * @return void
	 */
	public function group($group)
	{
		$this->db->group_by($group);
		return $this;
	}

}
