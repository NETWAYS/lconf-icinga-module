<?php


class LConf_LDAPFilterManagerModel extends IcingaBaseModel {

	public function getFilters() {
		$user = $this->getContext()->getUser();
		$uid = $user->getNsmUser()->get('user_id');
		
		$query = Doctrine_Query::create()
				->select('*')
				->from("LconfFilter lf")
				->innerJoin("lf.NsmUser user")
				->where("user.user_id = ?",$uid);
		return $query->execute()->toArray();
	}

	public function getFilterById($id) {
		$user = $this->getContext()->getUser();
		$uid = $user->getNsmUser()->get('user_id');
		$query = Doctrine_Query::create()
				->select('*')
				->from("LconfFilter lf")
				->innerJoin("lf.NsmUser user")
				->where("user.user_id = ?",$uid)
				->andWhere("lf.filter_id = ?",$id);
		return $query->execute()->toArray();		
	}
	
	public function removeFilters(array $filterIds) {
		$user = $this->getContext()->getUser();
		$uid = $user->getNsmUser()->get('user_id');
		$query = Doctrine_Query::create()
			->select('*')
			->from("LconfFilter lf")
			->innerJoin("lf.NsmUser user")
			->where("user.user_id = ?",$uid)
			->andWhereIn("lf.filter_id ",$filterIds);
		$result = $query->execute();
		foreach($result as $model)
			$model->delete();
	}
	
	public function addFilter($name,$json) {
		$user = $this->getContext()->getUser();
		$uid = $user->getNsmUser()->get('user_id');
		$model = new LconfFilter();
			$model->set("user_id",$uid);
			$model->set("filter_name",$name);
			$model->set("filter_json",$json);
		$model->save();		
	}
	
	public function modifyFilter($id,$json,$name = null) {
		$user = $this->getContext()->getUser();
		$uid = $user->getNsmUser()->get('user_id');
		$model = Doctrine_Query::create()
			->select('*')
			->from("LconfFilter lf")
			->innerJoin("lf.NsmUser user")
			->where("user.user_id = ?",$uid)
			->andWhere("lf.filter_id = ? ",$id)->execute()->getFirst();
		if(!$model)
			throw new Exception("Invalid id provided!");
		
		if($name)
			$model->set("filter_name",$name);
		$model->set("filter_json",$json);
		$model->save();
	}

	public function getFilterAsLDAPModel($id) {
	
	}
	
}