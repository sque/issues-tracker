<?php

class UI_IssuesGrid extends Output_HTML_Grid
{
    protected $issues;
    
    public function __construct($issues, $omit_fields = array('created', 'poster'))
    {   $this->issues = $issues;

        $fields = array(
            'id' => array('caption' => 'Id'),
            'title' => array('caption' => 'Title', 'customdata' => true),
            'project' => array('caption' => 'Project', 'mangle' => true),
            'status' => array('caption' => 'Status', 'mangle' => true),
            'assignee' => array('caption' => 'Assigned', 'mangle' => true),
            'created' => array('caption' => 'Post Date', 'mangle' => true),
            'poster' => array('caption' => 'Poster', 'mangle' => true),
            'last-activity' => array('caption' => 'Last Activity', 'customdata' => true),
            );
        foreach($omit_fields as $f)
            unset($fields[$f]);
        Output_HTML_Grid::__construct($fields, array(), $issues);
    }

    public function on_click($col_id, $row_id, $record)
    {
        UrlFactory::craft('issue.view', $record)->redirect();

    }
    
    public function on_custom_data($col_id, $row_id, $record)
    {
        if ($col_id == 'title')
            return UrlFactory::craft('issue.view', $record)->anchor($record->title);    
        if ($col_id == 'last-activity')
        {
            $last_action = $record->actions
                ->subquery()
                ->order_by('date', 'ASC')
                ->limit(1)
                ->execute();
            if (count($last_action) == 1)
                return date_exformat($last_action[0]->date)->smart_details();
            else
                return '---';
        }
    }
    public function on_mangle_data($col_id, $row_id, $data)
    {

        if ($col_id == 'project')
            return UrlFactory::craft('project.view', $data)->anchor($data->title);
        if ($col_id == 'poster')
            return tag_user($data);
        if ($col_id == 'assignee')
        {   
            if (!$data)
                return '---';
            return tag_user($data);
        }
        if ($col_id == 'status')
            return tag('span class="status"', $data)->add_class($data);
        if ($col_id == 'created')
            return date_exformat($data)->smart_details();

    }

}

?>
