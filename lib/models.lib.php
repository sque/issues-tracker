<?php

class Project extends DB_Record
{
    public static $table = 'projects';

    public static $fields = array(
        'name' => array('pk' => true),
        'title',
        'description',
        'created' => array('type' => 'datetime')
    );
}

class Membership extends DB_Record
{
    public static $table = 'memberships';
    
    public static $fields = array(
        'username' => array('pk' => true),
        'groupname' => array('pk' => true)
    );
}

class UserProfile extends DB_Record
{
    public static $table = 'user_profiles';
    
    public static $fields = array(
        'username' => array('pk' => true),
        'fullname',
        'email'
    );
}

class Attachment extends DB_Record
{
    public static $table = 'attachments';
    
    public static $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'filename',
        'filesize',
        'mime',
        'path'
    );
    
    public static function create_from_file($fname, $data, $save_folder)
    {
        // Get mime type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($data);
        
        // Calculate save_path
        $path_count = 0;
        $path = $save_folder . '/' . md5($data) . '.dat';
        while(file_exists($path))
            $path = $save_folder . '/' . md5($data) . '.' . ($path_count += 1) . '.dat';

        // Save data
        file_put_contents($path, $data);
        
        // Save entry
        $a = Attachment::create(array(
            'filename' => $fname,
            'filesize' => strlen($data),
            'path' => $path,
            'mime' => $mime
        ));
        return $a;
    }
    public function dump_file()
    {
        header('Content-Type: ' . $this->mime);
        echo file_get_contents($this->path);
    }
}

class Issue extends DB_Record
{
    public static $table = 'issues';

    public static $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'title',
        'description',
        'status',
        'project_name' => array('fk' => 'Project'),
        'created' => array('type' => 'datetime'),
        'poster',
        'assignee'
    );

    public function action_change_status($actor, $date, $status)
    {
        // Log action
        $create_args = array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'status_change');

        $action = IssueAction::create($create_args);
        $create_args = array(
            'id' => $action->id,
            'old_status' => $this->status,
            'new_status' => $status);
        DB_Record::create($create_args, 'IssueActionStatusChange');

        // Change action
        $this->status = $status;
        $this->save();
        return $action;
        
    }

    public function action_edit($actor, $date, $new_title, $new_description)
    {
        if (($new_description == $this->description) &&
                ($new_title == $this->title))
            return false;

        $create_args = array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'details_change');

        $action = IssueAction::create($create_args);
        $create_args = array(
            'id' => $action->id,
            'old_title' => $this->title,
            'new_title' => $new_title,
            'old_description' => $this->description,
            'new_description' => $new_description
        );
        DB_Record::create($create_args, 'IssueActionDetailsChange');
        
        $this->title = $new_title;
        $this->description = $new_description;
        $this->save();
        return $action;
    }

    public function action_comment($actor, $date, $post, $attachment)
    {
        if ($attachment)
        {
            if (!($a = Attachment::create_from_file($attachment['orig_name'], 
                    $attachment['data'], Config::get('issue.upload_folder'))))
                throw new RuntimeException('Error saving attachment.');
            $attachment_id = $a->id;
        }
        else
            $attachment_id = null;
        
        $create_args = array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'comment');

        $action = IssueAction::create($create_args);
        DB_Record::create(array('id' => $action->id, 'post' => $post, 'attachment_id' => $attachment_id), 'IssueActionComment');
        return $action;
    }

    public function action_add_tag($actor, $date, $tag)
    {
        // Log action
        $create_args = array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'tag_change');

        $action = IssueAction::create($create_args);
        $create_args = array(
            'id' => $action->id,
            'operation' => 'add',
            'tag' => $tag);
        DB_Record::create($create_args, 'IssueActionTagChange');

        // Add tag
        $create_tag = array('issue_id' => $this->id, 'tag' => $tag);
        IssueTag::create($create_tag);
        return $action;
    }

    public function action_remove_tag($actor, $date, $tag)
    {
        // Log action
        $create_args = array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'tag_change');

        $action = IssueAction::create($create_args);
        $create_args = array(
            'id' => $action->id,
            'operation' => 'remove',
            'tag' => $tag);
        DB_Record::create($create_args, 'IssueActionTagChange');

        // Add tag
        $t = IssueTag::open(array('issue_id' => $this->id, 'tag' => $tag));
        $t->delete();
        return $action;
    }
    
    public function tag_names()
    {
        $tags = array();
        foreach(IssueTag::open_query()
            ->where('issue_id = ?')
            ->execute($this->id) as $t)
        $tags[] = $t->tag;
        return $tags;
    }
}

class IssueStatus extends DB_Record
{
    public static $table = 'issue_statuses';

    public static $fields = array(
        'name' => array('pk' => true),
        'description'
    );
}

class IssueTag extends DB_Record
{
    public static $table = 'issue_tags';

    public static $fields = array(
        'issue_id' => array('pk' => true, 'fk' => 'Issue'),
        'tag' => array('pk' => true)
    );
}

class IssueAction extends DB_Record
{
    public static $table = 'issue_actions';

    public static $fields = array(
        'id' => array('pk' => 'true', 'ai' => true),
        'issue_id' => array('fk' => 'Issue'),
        'type',
        'actor',
        'date' => array('type' => 'datetime')
    );

    public function get_details()
    {
        if ($this->type == 'comment')
            return IssueActionComment::open($this->id);
        else if ($this->type === 'status_change')
            return IssueActionStatusChange::open($this->id);
        else if ($this->type === 'tag_change')
            return IssueActionTagChange::open($this->id);
        else if ($this->type === 'details_change')
            return IssueActionDetailsChange::open($this->id);
    }
}

class IssueActionComment extends DB_Record
{
    public static $table = 'issue_action_comments';

    public static $fields = array(
        'id' => array('pk' => true),
        'post',
        'attachment_id' => array('fk' => 'Attachment')
    );

    public function get_action()
    {
        return IssueAction::open($this->id);
    }
}

class IssueActionStatusChange extends DB_Record
{
    public static $table = 'issue_action_status_changes';

    public static $fields = array(
        'id' => array('pk' => true),
        'old_status',
        'new_status'
    );

    public function get_action()
    {
        return IssueAction::open($this->id);
    }
}

class IssueActionTagChange extends DB_Record
{
    public static $table = 'issue_action_tag_changes';

    public static $fields = array(
        'id' => array('pk' => true),
        'operation',
        'tag'
    );

    public function get_action()
    {
        return IssueAction::open($this->id);
    }
}

class IssueActionDetailsChange extends DB_Record
{
    public static $table = 'issue_action_details_changes';

    public static $fields = array(
        'id' => array('pk' => true),
        'old_title',
        'new_title',
        'old_description',
        'new_description'
    );

    public function get_action()
    {
        return IssueAction::open($this->id);
    }
}

Project::one_to_many('Issue', 'project', 'issues');
Issue::one_to_many('IssueAction', 'issue', 'actions');
Issue::one_to_many('IssueTag', 'issue', 'tags');
Attachment::one_to_many('IssueActionComment', 'attachment', 'issues');
?>
