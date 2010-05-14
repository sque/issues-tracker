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

class Issue extends DB_Record
{
    public static $table = 'issues';

    public static $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'title',
        'description',
        'status',
        'project_name' => array('fk' => 'Project'),
        'created' => array('type' => 'datetime')
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

    public function action_comment($actor, $date, $post)
    {
        $create_args = array(
            'actor' => $actor,
            'date' => $date,
            'issue_id' => $this->id,
            'type' => 'comment');

        $action = IssueAction::create($create_args);
        DB_Record::create(array('id' => $action->id, 'post' => $post), 'IssueActionComment');
        return $action;
    }

    public function action_add_tag($tag)
    {
    }

    public function remove_tag($tag)
    {
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

Project::one_to_many('Issue', 'project', 'issues');
Issue::one_to_many('IssueAction', 'issue', 'actions');

?>
