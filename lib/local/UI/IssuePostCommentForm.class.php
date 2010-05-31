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
                    'regcheck' => '/^.{3,}$/s',
                    'onerror' => 'You cannot add an empty comment'),
                'new-status' => array('display' => 'Status', 'type' => 'dropbox', 'optionlist' => $stats,
                    'value' => $this->issue->status),
                'attachment' => array('display' => 'Attachment', 'type' => 'file')
            ),
            array(
                'css' => array('ui-form', 'issue-post'),
                'buttons' =>
                    array('Post' => array())
            )
        );
    }

    public function on_valid($values)
    {
        // Comit comment
        $action = $this->issue->action_comment(
            Authn_Realm::get_identity()->id(),
            new DateTime(),
            $values['post'],
            $values['attachment']);

        // Send mail
        $ac = $action->get_details();
        $mail = new MailerIssue($this->issue, $ac->post .
            ($ac->attachment?
                "Attachments:\n" .
                UrlFactory::craft_fqn('attachment.view', $ac->attachment)
                :'')
        );
        $mail->send();
        
        // Change status if new one
        if ($values['new-status'] != $this->issue->status)
            $this->issue->action_change_status(
                Authn_Realm::get_identity()->id(),
                new DateTime(),
                $values['new-status']);

        if ($action)
        {
            $f = & $this->get_field('post');
            $f['value'] = '';
            $f = & $this->get_field('new-status');
            $f['value'] = $this->issue->status;
        }

        UrlFactory::craft('issueaction.view', $action)->redirect();
    }
}

?>
