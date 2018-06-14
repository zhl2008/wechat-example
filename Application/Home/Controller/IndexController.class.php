<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
	$this->show('<h1>Welcome to Haozigege\'s unknown battle ground !</h1>','utf8');
    }
}
