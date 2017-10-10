<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Mailer\Message\MessageRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/email-template")
 */
class AdminEmailTemplateController extends Controller
{
    /**
     * @Route(name="app_admin_email_template_list")
     * @Method("GET")
     * @Security("has_role('ROLE_APP_ADMIN_EMAIL_TEMPLATE_LIST')")
     */
    public function listAction(): Response
    {
        $registry = new MessageRegistry();
        $templates = $registry->getTypes();

        return $this->render('admin/email/template/list.html.twig', [
            'templates' => $templates,
        ]);
    }

    /**
     * @Route("/html/{name}", name="app_admin_email_template_html")
     * @Method("GET")
     * @Security("has_role('ROLE_APP_ADMIN_EMAIL_TEMPLATE_VIEW')")
     */
    public function htmlAction(string $name): Response
    {
        return $this->renderBlock($name, 'body_html');
    }

    /**
     * @Route("/text/{name}", name="app_admin_email_template_text")
     * @Method("GET")
     * @Security("has_role('ROLE_APP_ADMIN_EMAIL_TEMPLATE_VIEW')")
     */
    public function textAction(string $name): Response
    {
        return $this->renderBlock($name, 'body_text');
    }

    private function renderBlock(string $templateName, string $blockName): Response
    {
        // profiler toolbar should not interact with the template content
        $this->get('profiler')->disable();

        $template = $this->get('twig')->load(sprintf('email/template/%s_message.html.twig', $templateName));

        if (!$template->hasBlock($blockName)) {
            throw $this->createNotFoundException(sprintf('The template "%s" has no "%s" block.', $templateName, $blockName));
        }

        return new Response($template->renderBlock($blockName));
    }
}
