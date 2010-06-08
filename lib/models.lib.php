<?php

class Project extends DB_Record
{
    public static $table = 'projects';

    public static $fields = array(
        'name' => array('pk' => true),
        'title',
        'description',
        'created' => array('type' => 'datetime'),
        'manager'
    );
    
    public function update_counters()
    {
        // Delete previous
        ProjectTagCount::raw_query()
            ->delete()
            ->where('project_name = ?')
            ->execute($this->name);
            
        $tags =IssueTag::open_query()
            ->left_join('Issue', 'issue_id','id')
            ->where('l.project_name = ?')
            ->execute($this->name);

        $max = 0;
        $tags_counter = array();
        foreach($tags as $t)
        {   if (!isset($tags_counter[$t->tag]))
                $tags_counter[$t->tag] = 0;
            $tags_counter[$t->tag] += 1;
            
            if ($tags_counter[$t->tag] > $max)
                $max = $tags_counter[$t->tag];
        }
        $min = $max;
        foreach($tags_counter as $count)
            if ($count < $min)
                $min = $count;
        $breadth = $max - $min;
        // Save to database
        foreach($tags_counter as $tag => $count)
            ProjectTagCount::create(array(
                'project_name' => $this->name,
                'tag' => $tag,
                'count' => $count,
                'percent' => ($breadth?($count - $min) / $breadth:1.0)
            ));
    }
}

class ProjectTagCount extends DB_Record
{
    public static $table = 'project_tag_count';

    public static $fields = array(
        'project_name' => array('pk' => true, 'fk' => 'Project'),
        'tag' => array('pk' => true),
        'count',
        'percent'
    );
}

class Membership extends DB_Record
{
    public static $table = 'memberships';
    
    public static $fields = array(
        'username' => array('pk' => true),
        'groupname' => array('pk' => true)
    );
    
    //! Get users fetching extra data from profile
    /**
     * @param $profile_attribute
     *  - @b string The name of the attribute
     *  - @b false dont fetch profile
     *  - @b true Fetch an array with all attributes
     *  .
     */
    public static function get_users($groupname, $profile_attribute = 'fullname')
    {
        $users = array();
        foreach(Membership::open_query()
            ->where('groupname = ?')
            ->execute($groupname) as $m)
        {
            
            if (($profile_attribute !== false) && ($p = UserProfile::open($m->username)))
            {   
                if ($profile_attribute === true)
                {

                    $users[$m->username] = array();
                    foreach(UserProfile::model()->fields() as $fname)
                        $users[$m->username][$fname] = $p->$fname;
                }
                else
                    $users[$m->username] = $p->$profile_attribute;
            }
            else
                $users[$m->username] = $m->username;
        }
        return $users;
    }
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
        'issue_action_id' => array('fk' => 'IssueActionComment'),
        'filename',
        'filesize',
        'mime',
        'path'
    );
    
    public static function create_from_file($action_id, $fname, $data, $save_folder)
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
            'issue_action_id' => $action_id,
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

    public function action_edit($actor, $date, $new_title, $new_description, $removed_tags, $added_tags, $new_assignee)
    {
        if (
            ($new_title == $this->title) &&
            ($new_description == $this->description) &&
            ($new_assignee == $this->assignee) &&
            (empty($added_tags)) &&
            (empty($removed_tags))
        )
            return false;
            
        // Create action
        $action = IssueAction::create(array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'details_change'));
            
        IssueActionDetailsChange::create(array(
            'id' => $action->id,
            'old_title' => ($this->title != $new_title)?$this->title:'',
            'new_title' => ($this->title != $new_title)?$new_title:'',
            'old_description' => ($this->description != $new_description)?$this->description:'',
            'new_description' => ($this->description != $new_description)?$new_description:'',
            'old_assignee' => ($this->assignee != $new_assignee)?$this->assignee:'',
            'new_assignee' => ($this->assignee != $new_assignee)?$new_assignee:'',
            'removed_tags' => implode(' ', $removed_tags),
            'added_tags' => implode(' ', $added_tags)
        ));

        // Save issue details
        $this->title = $new_title;
        $this->description = $new_description;
        $this->assignee = $new_assignee;
        $this->save();
                
        // Save tag changes
        foreach($added_tags as $tag)
            IssueTag::create(array('issue_id' => $this->id, 'tag' => $tag));
        foreach($removed_tags as $tag)
            IssueTag::open(array('issue_id' => $this->id, 'tag' => $tag))
                ->delete();
        if ( (!empty($added_tags)) || (!(empty($removed_tags))) )
            $this->project->update_counters();

        return $action;
    }

    public function action_comment($actor, $date, $post)
    {
        $action = IssueAction::create(array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'comment'));
        $action = IssueActionComment::create(array(
            'id' => $action->id,
            'post' => $post
        ));
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
        else if ($this->type === 'details_change')
            return IssueActionDetailsChange::open($this->id);
    }
}

class IssueActionComment extends DB_Record
{
    public static $table = 'issue_action_comments';

    public static $fields = array(
        'id' => array('pk' => true),
        'post'
    );

    public function get_action()
    {
        return IssueAction::open($this->id);
    }
    
    public function save_attachment($attachment)
    {
        if (!$attachment)
            return;

        // Save attachment
        $a = Attachment::create_from_file(
            $this->id,
            $attachment['orig_name'], 
            $attachment['data'],
            Config::get('issue.upload_folder')
        );
        
        if (!$a)
            throw new RuntimeException('Error saving attachment.');
        return $a;
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

class IssueActionDetailsChange extends DB_Record
{
    public static $table = 'issue_action_details_changes';

    public static $fields = array(
        'id' => array('pk' => true),
        'old_title',
        'new_title',
        'old_description',
        'new_description',
        'old_assignee',
        'new_assignee',
        'removed_tags',
        'added_tags'
    );

    public function get_action()
    {
        return IssueAction::open($this->id);
    }
}

Project::one_to_many('Issue', 'project', 'issues');
Project::one_to_many('ProjectTagCount', 'project', 'tag_counters');
Issue::one_to_many('IssueAction', 'issue', 'actions');
Issue::one_to_many('IssueTag', 'issue', 'tags');
IssueActionComment::one_to_many('Attachment', 'action', 'attachments');
?>
