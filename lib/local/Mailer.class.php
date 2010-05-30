<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */


require_once('Mail.php');

class Mailer
{
    //! Instance of Pear::Mail
    static private $mail = null;
    
    //! Default headers
    static private $headers = array(
        'X-Generated-By' => 'Issues Tracker');
    
    //! Set the mail instance
    static public function set_mail_instance(Mail $instance)
    {
        self::$mail = $instance;
    }
    
    //! Set default headers
    static public function set_default_headers($headers)
    {
        if (is_array($headers))
            self::$headers = $headers;
    }
    
    //Get mail instance
    static public function get_mail_instance()
    {
        return self::$mail;
    }

    //! Get default headers
    static public function get_default_headers()
    {
        return self::$headers;
    }

    //! Send a mail to a trackers group
    static public function send_group_mail($groupname, $subject, $body, $extra_headers = array())
    {
        return self::send_users_mail
            (Membership::get_users($groupname, false), false);
    }

    //! Send a mail to one user
    static public function send_user_mail($username, $subject, $body, $extra_headers = array())
    {
        if (!($p = UserProfile::open($username)))
            return false;

        if (!$p->email)
            return false;

        $headers['Subject'] = $subject;
        $headers['To'] = "{$p->fullname} <{$p->email}>";
        
        // Add default headers
        $headers = array_merge(self::$headers, $extra_headers, $headers);
        
        // Craft body
        self::$mail->send($p->email, $headers, $body);
    }
    
    //! Send a mail a set of users
    static public function send_users_mail($users, $subject, $body, $extra_headers = array(), $individual = true)
    {
        if ($individual)
        {
            foreach($users as $u)
                self::send_user_mail($u, $subject, $body, $extra_headers);
            return;
        }

                
    }
}
?>
