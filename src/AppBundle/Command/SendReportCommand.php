<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hermod:report:send')
            ->setDescription('Send report by mail.')
            ->setHelp('This command send email with report file.')
            ->addArgument('to', InputArgument::REQUIRED, 'Email of recipient in to')
            ->addArgument('cc', InputArgument::IS_ARRAY, 'Emails of recipients in cc');
    }

    private function getCsvReport()
    {
        $date = new \DateTime();
        $endDate  = $date->format('Y-m-d');
        $startDate = $date->modify('-7 days')->format('Y-m-d');
        $report = $this->getContainer()->get('AppBundle\Services\LocationPatch')->getCsvReportByPeriod($startDate, $endDate);

        return (new \Swift_Attachment())
            ->setFilename('reporting_location_patch_between_' . $startDate . '_and_' . $endDate . '.csv')
            ->setContentType('text/csv')
            ->setBody($report)
        ;
    }

    private function getMessage($to, $cc) : \Swift_Message
    {
        $templating = $this->getContainer()->get('templating');

        return (new \Swift_Message())
            ->setSubject('[HERMOD] - Export des signalements d\'arrêt')
            ->setFrom(['hermod@kisio.org' => 'Hermod team'])
            ->setTo($to)
            ->setCc($cc)
            ->setBody(
                $templating->render('AppBundle:Emails:registration.html.twig'),
                'text/html'
            )
            ->attach($this->getCsvReport());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailer = $this->getContainer()->get('mailer');
        $io = new SymfonyStyle($input, $output);
        $to = $input->getArgument('to');
        $cc = $input->getArgument('cc');
        $message = $this->getMessage($to, $cc);

        $mailer->send($message);
        $io->success('[' . date("Y-m-d h:i:s") . '] - Message sent to: "'. $to .'" cc: "'. implode(', ', $cc) .'"');
    }
}
