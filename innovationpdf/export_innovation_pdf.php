<?php
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    local_innoedtools
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/blog/locallib.php'); //file_rewrite_pluginfile_urls
require_once('mypdf.php');
require_once($CFG->dirroot.'/local/innoedtools/attributes/report_tag_by_overall.php');

/**
 * Business logic of export pdf.
 * 
 * @copyright  2016 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_innovation_pdf {

    /** Pdf object. */
    protected $pdf = null;
    /** Preview mode. */
    protected $is_preview = 0;
    /** Current user id. */
    protected $current_user = 0;
    /** Object of Current user. */
    protected $user_obj;

    /** Left margin. */
    protected $leftmargin = 0;
    /** Top margin. */
    protected $topmargin = 0;
    /** Right margin. */
    protected $rightmargin = 0;
    /** Bottom margin. */
    protected $bottommargin = 0;
    /** Line height. */
    protected $linesize = 0;
    /** Top margin to first line of body. */
    protected $bodymargin = 0;

    /** Paper A4 width. */
    protected $A4_width = 0;
    /** Paper A4 height. */
    protected $A4_height = 0;
    /** Body width in protrait. */
    protected $bodywidth_portrait = 0;
    /** Body width in landscape. */
    protected $bodywidth_landscape = 0;
    /** Body height in protrait. */
    protected $bodyheight_portrait = 0;
    /** Body height in landscape. */
    protected $bodyheight_landscape = 0;

    /** Cover page title font size. */
    protected $covertitlesize = 0;
    /** Cover page description font size. */
    protected $coverdescriptionsize = 0;
    /** Page title font size. */
    protected $pagetitlesize = 0;
    /** Page description font size. */
    protected $pagedescriptionsize = 0;
    /** Default font size. */
    protected $defaultsize = 0;

    /** Cover background image file. */
    protected $coverimagefile = '';
    /** Page background image file. */
    protected $backgroundimagefile = '';

    /** QR code settings. */
    protected $style_qrcode = array();

    /**
     * Constructor
     */
    public function __construct($preview = 0) {
        $this->is_preview = $preview;
        $this->initial_current_user();
        $this->initial_variable();
    }

    /**
     * Initial current user
     */
    protected function initial_current_user() {
        global $USER, $DB;

        $uid = optional_param('uid', null, PARAM_INT);
        $this->current_user = isset($uid) ? $uid : $USER->id;
        $this->user_obj = $DB->get_record('user', array('id' => $this->current_user));
    }

    /**
     * Initial value of all variables
     */
    protected function initial_variable() {
        $this->initial_variable_page_margin();
        $this->initial_variable_page_size();
        $this->initial_variable_font_size();
        $this->initial_variable_background_image();
        $this->initial_variable_qr_code();
    }

    /**
     * Initial value of page margin
     */
    protected function initial_variable_page_margin() {
        $this->leftmargin = 15;
        $this->topmargin = 20;
        $this->rightmargin = 15;
        $this->bottommargin = 15;
        $this->linesize = 15;
        $this->bodymargin = $this->topmargin + $this->linesize + $this->linesize;
    }

    /**
     * Initial value of page size
     */
    protected function initial_variable_page_size() {
        $this->A4_width = 210;
        $this->A4_height = 297;

        $this->bodywidth_portrait = $this->A4_width - $this->leftmargin - $this->rightmargin;
        $this->bodywidth_landscape = $this->A4_height - $this->leftmargin - $this->rightmargin;
        $this->bodyheight_portrait = $this->A4_height - $this->bodymargin - $this->bottommargin;
        $this->bodyheight_landscape = $this->A4_width - $this->bodymargin - $this->bottommargin;
    }

    /**
     * Initial value of font size
     */
    protected function initial_variable_font_size() {
        $this->covertitlesize = 36;
        $this->coverdescriptionsize = 24;
        $this->pagetitlesize = 18;
        $this->pagedescriptionsize = 16;
        $this->defaultsize = 12;
    }

    /**
     * Initial value of background image
     */
    protected function initial_variable_background_image() {
        global $CFG;

        $this->coverimagefile = $CFG->dirroot.'/local/innoedtools/innovationpdf/image/'.get_string('coverbackgroundimagefile', 'local_innoedtools');
        $this->backgroundimagefile = $CFG->dirroot.'/local/innoedtools/innovationpdf/image/'.get_string('backgroundimagefile', 'local_innoedtools');
    }

    /**
     * Initial value of qr code settings
     */
    protected function initial_variable_qr_code() {
        $this->style_qrcode = array(
            'border' => 2,
            'vpadding' => 'auto', 
            'hpadding' => 'auto', 
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, 
            'module_width' => 1, 
            'module_height' => 1 
        ); 
    }

    /**
     * Render data to pdf view
     */
    public function print_blog() {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE, $SITE;

        $rows_blogs = $this->get_innovation_blog();
        
        $systemcontext = context_system::instance();
        // Check if user try to view blog of someone else
        if (!(is_siteadmin() || has_capability('local/innoedtools:viewallinnovationpdf', $systemcontext))) {
            if ($this->current_user != $USER->id) {
                $returnurl = new moodle_url('/local/innoedtools/innovationpdf/index.php?uid='.$this->current_user);
                redirect($returnurl);
            }
        }

        if(count($rows_blogs) == 0) {
            $returnurl = new moodle_url('/local/innoedtools/innovationpdf/index.php?uid='.$this->current_user);
            redirect($returnurl);
        } else {
            // PDF
            $this->create_pdf_object();

            // Cover page
            $this->generate_cover_page();
            foreach ($rows_blogs as $entry) {

                // Content
                $this->generate_content_page($entry);
            }

            // Last page
            $this->generate_last_page();
        }

        // Filename
        $filename = $this->user_obj->username.'_portfolio';
        $downloadfilename = clean_filename($filename.".pdf");

        if($this->is_preview == '1') {
            $this->pdf->Output($downloadfilename);
        } else {
            $this->pdf->Output($downloadfilename, 'D');
        }

        exit;
    }

    /**
     * Get blog entries
     */
    protected function get_innovation_blog() {
        global $DB;

        $sql_blog_list = "  SELECT p.id, p.userid, p.courseid, p.subject, p.summary
                            FROM {post} p
                            INNER JOIN {course} c
                            ON p.courseid = c.id
                            WHERE p.courseid <> 0
                            AND p.module = 'blog'
                            AND c.idnumber <> ''
                            AND c.visible = 1
                            AND p.userid = $this->current_user
                            ORDER BY courseid ASC 
                        ";
        $rows_blogs = $DB->get_records_sql($sql_blog_list);

        return $rows_blogs;
    }

    /**
     * Create pdf object
     */
    protected function create_pdf_object() {
        // Default orientation is Portrait
        $orientation = 'P';

        $this->pdf = new mypdf($orientation, 'mm', array($this->A4_width, $this->A4_height), true, 'UTF-8');

        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins($this->leftmargin, $this->topmargin, -1, true);
        $this->pdf->SetAutoPageBreak(true, $this->bottommargin);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    /**
     * Render data on cover page.
     */
    protected function generate_cover_page() {
        global $DB;

        // Cover page
        $this->pdf->AddPage();

        // Override - Cover background image
        $bMargin = $this->pdf->getBreakMargin();
        $auto_page_break = $this->pdf->getAutoPageBreak();
        $this->pdf->SetAutoPageBreak(false, 0);
        $this->pdf->Image($this->coverimagefile, 0, 0, $this->A4_width, $this->A4_height, '', '', '', false, 300, '', false, false, 0);
        $this->pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        $this->pdf->setPageMark();

        // Report title
        $covertitle = 'Portfolio';
        $this->pdf->SetFontSize($this->covertitlesize);
        $this->pdf->writeHTMLCell($this->bodywidth_portrait, 0, $this->leftmargin, 100, $covertitle, 0, 0, false, true, 'C');

        // Student name
        $coverdescription = fullname($this->user_obj);
        $this->pdf->SetFontSize($this->coverdescriptionsize);
        $this->pdf->writeHTMLCell($this->bodywidth_portrait, 0, $this->leftmargin, 130, $coverdescription, 0, 0, false, true, 'C');

        // Student information
        $email = empty($this->user_obj->email) ? '-' : $this->user_obj->email;
        $email = 'Email: ' . $email;
        $this->pdf->SetFontSize($this->defaultsize);
        $this->pdf->writeHTMLCell($this->bodywidth_portrait, 0, $this->leftmargin, 145, $email, 0, 0, false, true, 'C');

        $mobile = empty($this->user_obj->phone2) ? '-' : $this->user_obj->phone2;
        $mobile = 'Mobile: ' . $mobile;
        $this->pdf->SetFontSize($this->defaultsize);
        $this->pdf->writeHTMLCell($this->bodywidth_portrait, 0, $this->leftmargin, 152, $mobile, 0, 0, false, true, 'C');                       
    }

    /**
     * Render data on content page.
     */
    protected function generate_content_page($entry) {
        global $DB;

        $course_obj = $DB->get_record('course', array('id' => $entry->courseid));

        if ($course_obj) {
            $this->pdf->startPageGroup();
            $this->pdf->AddPage();

            // Course title
            $coursetitle = $course_obj->fullname;
            $this->pdf->SetFontSize($this->pagetitlesize);
            $this->pdf->writeHTMLCell($this->bodywidth_portrait, 0, $this->leftmargin, $this->topmargin, $coursetitle, 0, 1, false, true, 'C');

            // Blog title
            $blogsubject = $entry->subject;
            $this->pdf->SetFontSize($this->pagedescriptionsize);
            $this->pdf->writeHTMLCell($this->bodywidth_portrait, 0, $this->leftmargin, $this->topmargin + $this->linesize, $blogsubject, 0, 1, false, true, 'C');

            // Blog content
            $summary = file_rewrite_pluginfile_urls($entry->summary, 'pluginfile.php', SYSCONTEXTID, 'blog', 'post', $entry->id);
            $summary = $this->process_content_images($summary);
            $summary = $this->process_content_links($summary);
            $data = $summary;

            // Blog tags
            $tagArr = core_tag_tag::get_item_tags_array('core', 'post', $entry->id); 
            if($tagArr) {
                $tag = "";
                foreach ($tagArr as $key => $value) {
                    $tag .= ", ".$value;
                }
                $tag = substr($tag, 1);
            }
            $data .= "<b>Keywords / Skills : </b>".$tag."<br />";

            $this->pdf->SetFontSize($this->defaultsize);
            $this->pdf->writeHTMLCell($this->bodywidth_portrait, $this->bodyheight_portrait, $this->leftmargin, $this->bodymargin, $data, 0, 1, false, true, 'L');
        }
    }

    /**
     * Render data on last page (Tag report of student).
     */
    protected function generate_last_page() {
        global $DB;

        $this->pdf->AddPage();

        $tagreporttitle = get_string('managepageheading', 'local_innoedtools');
        $this->pdf->SetFontSize($this->pagetitlesize);
        $this->pdf->writeHTMLCell($this->A4_width, 0, 0, $this->topmargin, $tagreporttitle, 0, 0, false, true, 'C');

        $report_tag_by_overall = new report_tag_by_overall(false);
        $table = $report_tag_by_overall->generate_report_pdf();

        $this->pdf->SetFontSize($this->defaultsize);
        $this->pdf->writeHTMLCell($this->bodywidth_portrait, $this->bodyheight_portrait, $this->leftmargin, $this->bodymargin, html_writer::table($table), 0, 0, false, true, 'C');
    }

    /**
     * Process to convert image moodle path -> image server path. Then pdf can show images.
     */
    private function process_content_images($content) {
        global $CFG;
        $replacements = array();
        $tmpdir = make_temp_directory('files');

        // Does not support theme images.
        // Process pluginfile images.
        $imagetypes = get_string('imagetypes', 'local_innoedtools');
        if (preg_match_all("%$CFG->wwwroot/pluginfile.php(/[^.]+.($imagetypes))%", $content, $matches)) {
            $replacements = array();
            $fs = get_file_storage();
            foreach ($matches[1] as $imagepath) {
                if (!$file = $fs->get_file_by_hash(sha1($imagepath)) or $file->is_directory()) {
                    continue;
                }

                $filename = $file->get_filename();
                $filepath = "$tmpdir/$filename";
                if ($file->copy_content_to($filepath)) {
                    $replacements["$CFG->wwwroot/pluginfile.php$imagepath"] = $filepath;
                    $this->_tmpfiles[] = $filepath;
                }
            }
        }
       
        // Replace content.
        if ($replacements) {
            $content = str_replace(array_keys($replacements), $replacements, $content);
        }

        return $content;
    }

    /**
     * Process to convert link (<a> tag) -> QR code.
     */
    private function process_content_links($content) {
        global $CFG;
        $replacements = array();

        $dom = new DomDocument();
        $dom->loadHTML($content);
        $list_of_links = array();
       
        foreach ($dom->getElementsByTagName('a') as $item) {      
            $list_of_links[] = array (
                'str' => $dom->saveHTML($item),
                'href' => $item->getAttribute('href'),
                'text' => $item->nodeValue
            );
        }

        foreach($list_of_links as $value){
            $str = $value['str'];
            $href = $value['href'];

            // Serialize QR Code
            $qrcode = $this->generate_qrcode($href);
            $replacements[$str] = $qrcode;
        }

        // Replace content.
        if ($replacements) {
            $content = str_replace(array_keys($replacements), $replacements, $content);
        }
        return $content;
    }

    /**
     * Serialize QR Code into HTML tag
     */
    private function generate_qrcode($href) {
        $params = $this->pdf->serializeTCPDFtagParameters(array($href, 'QRCODE,Q', '', '', 25, 25, $this->style_qrcode, 'N'));
        $qrcode = '<br><tcpdf method="write2DBarcode" params="'.$params.'" />';

        return $qrcode;
    }
}