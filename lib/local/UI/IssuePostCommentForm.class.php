<?php


class UI_IssuePostCommentForm extends Output_HTML_Form
{
    private $issue;

    public function __construct($issue)
    {
        $this->issue = $issue;
        $stats = array();
        foreach(IssueStatus::open_all() as $s)
            $stats[$s->name] = $s->name;
            
        parent::__construct(
            array(
                'post' => array('display' => 'Comment', 'type' => 'textarea',
                    'regcheck' => '/^.{3,}$/',
                    'onerror' => 'You cannot add an empty comment'),
                'new-status' => array('display' => 'Status', 'type' => 'dropbox', 'optionlist' => $stats,
                    'value' => $this->issue->status)
            ),
            array(
                'buttons' =>
                    array('Post' => array())
            )
        );
    }

    public function on_valid($values)
    {
        $action = $this->issue->action_comment(
            Auth_Realm::get_identity(),
            new DateTime(),
            $values['post']);

        if ($values['new-status'] != $this->issue->status)
            $this->issue->action_change_status(
                Auth_Realm::get_identity(),
                new DateTime(),
                $values['new-status']);

        if ($action)
        {
            $f = & $this->get_field('post');
            $f['value'] = '';
            $f = & $this->get_field('new-status');
            $f['value'] = $this->issue->status;
        }
    }
}
