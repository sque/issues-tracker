<?php


class MailerIssue
{
    private $issue;
    
    private $body = '';
    
    private $signature = '';
    
    public function __construct(Issue $issue, $body, $actor = null)
    {
        $this->issue = $issue;
        $this->body = $body;
    }
    
    public function get_title()
    {   $i = $this->issue;
        return "[#{$i->id} {$i->project->name}] | $i->title";
    }
    
    public function get_body($receive_reason)
    {   $i = $this->issue;
        return $this->body . 
            "
            
            -- 
            " .
            "Project: {$i->project->title}\n" .
            "Issue: [#{$i->id}] {$i->title}\n" .
            "       " . UrlFactory::craft_fqn('issue.view', $i) . "\n" .
            $receive_reason;
    }
    public function send()
    {   
        if ((!($p = UserProfile::open(Authn_Realm::get_identity()->id()))) || empty($p->email))
            $extra_headers = array();
        else
            $extra_headers = array('From' => "{$p->fullname} <{$p->email}>");
        $extra_headers['X-IssuesTracker-Issue'] = $this->issue->id;
        
        $receipients = array();
        
        // Involved
        $involved = array($this->issue->poster => $this->issue->poster);
        foreach($this->issue->actions->all() as $a)
            $involved[$a->actor] = $a->actor;
        $receipients = $involved;
        Mailer::send_users_mail(array_keys($involved),
            $this->get_title(), 
            $this->get_body("You received this mail because you are\ntaking part in issue's conversation"),
            $extra_headers);
            
        // Admins
        $admins = array_diff(Membership::get_users('admin', false), $receipients);
        $receipients = array_merge($receipients, $admins);
        Mailer::send_users_mail(array_keys($admins),
            $this->get_title(), 
            $this->get_body("You received this mail because you belong\non the group of administrators"),
            $extra_headers);
            

        // Project maintainer
        if (!array_key_exists($this->issue->project->manager, $receipients))
        {
            Mailer::send_user_mail($this->issue->project->manager,
                $this->get_title(), 
                $this->get_body('You received this mail because you are project supervisor.'),
                $extra_headers);
        }
    }
}
?>
