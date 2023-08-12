<?php

class User_Profile
{
  private $email;
  private $user_id;
  private $showemail;
  private $avatar;
  private $friends;
  private $name;
 
  public function __construct($user_id, $email, $showemail, $avatar, $friends, $name)
  {
    $this->email = $email;
    $this->user_id = $user_id;
    $this->showemail = $showemail;
    $this->avatar = $avatar;
    $this->friends = $friends;
    $this->name = $name;
  }
  public function get_user_id()
  {
    return $this->user_id;
  }
  public function get_email()
  {
    return $this->email;
  }
  public function get_showemail()
  {
    return (!empty($this->showemail)) ? 'checked' : '';
  }
  public function get_avatar()
  {
    $friend_avatar=(!empty($this->avatar)) ? $this->avatar : './image/avatar.png';
    return $friend_avatar;
  }
  public function get_friends()
  {
    return $this->friends;
  }
   public function get_name()
  {
    return (empty($this->name)) ? $this->email : $this->name;
  } 
}