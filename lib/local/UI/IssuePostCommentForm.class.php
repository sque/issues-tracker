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
                'more' => array('type' => 'custom', 'value' => '<a class="ui-form-more">More ...</a>'),
                'new-status' => array('display' => 'Status', 'type' => 'dropbox', 'optionlist' => $stats,
                    'value' => $this->issue->status),
                'attachment1' => array('display' => 'Attachment', 'type' => 'file'),
                'attachment2' => array('display' => '', 'type' => 'file'),
                'attachment3' => array('display' => '', 'type' => 'file'),
                'attachment4' => array('display' => '', 'type' => 'file'),
                'attachment5' => array('display' => '', 'type' => 'file'),
                'attachment6' => array('display' => '', 'type' => 'file'),
                'attachment7' => array('display' => '', 'type' => 'file'),
                'attachment8' => array('display' => '', 'type' => 'file'),
                'attachment9' => array('display' => '', 'type' => 'file'),
                'attachment10' => array('display' => '', 'type' => 'file'),
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
            $values['post']);
    
        // Add attachments on comment
        $attachments_mail_description = '';
        for($i = 1;$i <= 10;$i++)
        {
            if ($values["attachment{$i}"])
            {
                $attach = $action->save_attachment($values["attachment{$i}"]);
                $attachments_mail_description .= UrlFactory::craft_fqn('attachment.view', $attach) . "\n";
            }
        }

        // Send mail
        $mail = new MailerIssue($this->issue, $action->post .
            ($attachments_mail_description?
                "\n\nAttachments:\n" .
                $attachments_mail_description
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

        UrlFactory::craft('issueaction.view', $action->get_action())->redirect();
    }
}

?>
