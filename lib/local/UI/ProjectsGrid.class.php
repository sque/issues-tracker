<?php

class UI_ProjectsGrid extends Output_HTML_Grid
{
    protected $projects;
    
    public function __construct($projects, $omit_fields = array('created'))
    {   $this->projects = $projects;

        $fields = array(
            'name' => array('caption' => 'Nick name'),
            'title' => array('caption' => 'Title / Description', 'customdata' => true),
            'issues' => array('caption' => 'Issues', 'customdata' => true),
            'manager' => array('caption' => 'Supervisor', 'mangle' => true),
            'created' => array('caption' => 'Registered', 'mangle' => true),
            );
        foreach($omit_fields as $f)
            unset($fields[$f]);
        Output_HTML_Grid::__construct($fields, array(), $projects);
    }

    public function on_custom_data($col_id, $row_id, $record)
    {
        if ($col_id == 'title')
            return UrlFactory::craft('project.view', $record)->anchor($record->title) .
                tag('p', text_sample($record->description, 150));
        if ($col_id == 'issues')
        {
            $count = array();
            foreach(IssueStatus::open_all() as $status)
            {
                $query_count = Issue::raw_query()
                    ->select(array('count(*)'))
                    ->where('project_name = ?')
                    ->where('status = ?')
                    ->execute($record->name, $status->name);
                if ($query_count[0][0])
                    $count[$status->name] = 
                        tag('span class="status"', $query_count[0][0] . ' ' . $status->name)
                            ->add_class($status->name);
            }
            return implode(', ', $count);
        }
    }
    public function on_mangle_data($col_id, $row_id, $data)
    {
        if ($col_id == 'project')
            return UrlFactory::craft('project.view', $data)->anchor($data->title);
        if ($col_id == 'created')
            return date_exformat($data)->smart_details();
        if ($col_id == 'manager')
            return tag_user($data);

    }

}

?>
