<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Version details.
 * @package    blocks
 * @subpackage assessment_information
 * @copyright  2015 Queen Mary University of London Shubhendra Doiphode
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
defined('TOPIC_ZERO_SECTION') || define('TOPIC_ZERO_SECTION','52');

class block_assessment_information_renderer extends plugin_renderer_base
{
	private $coursepage;
	private $modinfo;

	function block_content(&$content, $instanceid, $config, $assessment_information){

		global $COURSE;

		$this->coursepage = $this->page;
		$this->modinfo = get_fast_modinfo($COURSE);

		$section_wrapper = 'section-wrap';

        $this->page->requires->js_init_call('M.block_assessment_information.toggle_block', array(array(
                "showText" => get_string('show_block_text', 'block_assessment_information')." ▲",
                "hideText" => get_string('hide_block_text', 'block_assessment_information')." ▼"
            ))
        );

        if ($this->page->user_is_editing()){
            //add required javascripts
            $this->page->requires->js_init_call('M.block_assessment_information.add_handles');
            $this->page->requires->yui_module('moodle-topiczero-modchooser',
            'M.block_assessment_information.init_chooser',
                array(
                    array(
                        'courseid' => $COURSE->id,
                        'closeButtonTitle' => get_string('close', 'editor')
                    )
                )
            );
            //set section class to editing mode
            $section_wrapper = 'section-wrap editing';
        }
        //start content
        $html = '';
        //block toggle start
        $html .= html_writer::start_tag('p', array('class'=>'show-content'));
        $html .= html_writer::link(
            'javascript:void(0)',
            get_string('hide_block_text', 'block_assessment_information').' ▼',
            array('id'=>'aTag')
        );
        $html .= html_writer::end_tag('p');
        //block toggle end

        //block content start
        $html .= html_writer::start_div($section_wrapper, array('id'=>'show-content',
            'style'=>'min-height:0px'));

        //assessment information start
        $html .= html_writer::start_div(null, array('id'=>'coursebox-1'));
        //header


        $assessment_header = isset($config->assessment_header) ? $config->assessment_header
            : get_string('default_assessment_header', 'block_assessment_information');
            
        $html .= html_writer::tag('h2', $assessment_header);
        //list assessment resources
        if($this->coursepage->user_is_editing()){
            $resources = $assessment_information->get_course_resources('assessment',0);
        } else {
            $resources = $assessment_information->get_course_resources('assessment');
        }
        $html .= $this->get_resources_list($resources,'assessment');

        //$courserenderer = $this->page->get_renderer('core','course');
        $html .= $this->get_activity_chooser_control  ($COURSE,
            $assessment_information->topic_zero_section);

        $html .= html_writer::end_div();
        //assessment overview end

        //submit assignments start
        $html .= html_writer::start_div('',array('id'=>'coursebox-2'));
        //header
        $assignment_header = isset($config->assignment_header)
            ? $config->assignment_header
            : get_string('default_assignment_header', 'block_assessment_information');
        $html.= html_writer::tag('h2', $assignment_header);
        if(isset($config->assignment_text) && $config->assignment_text['text']){
            $html .= html_writer::tag('p', $config->assignment_text['text'],
                array('class'=>'subheadings_text')
            );
        }
        //list assignment resources
        if($this->coursepage->user_is_editing()){
            $resources = $assessment_information->get_course_resources('assignment',0);
        } else {
            $resources = $assessment_information->get_course_resources('assignment');
        }
        $html .= $this->get_resources_list($resources,'assignment');

        //subheadings start
        if(isset($config->subheadings_title) && $config->subheadings_title){
            $html .= $this->get_assignment_subheadings($assessment_information, $this, $config);
        }
        //subheadings end
        $html .= html_writer::end_div();
        //submit assignments end

        //extra section start
        if(isset($config->enable_extra_section) && $config->enable_extra_section){
            $html .= html_writer::start_div('',array('id'=>'coursebox-1'));
            //header
            $extra_section_header = isset($config->extra_section_header)
                ? $config->extra_section_header
                : get_string('default_extra_section_header', 'block_assessment_information');
            $html.= html_writer::tag('h2', $extra_section_header);
            //list subheadings resources
            if($this->coursepage->user_is_editing()){
                $resources = $assessment_information->get_course_resources('extra',0);
            } else {
                $resources = $assessment_information->get_course_resources('extra');
            }
            $html .= $this->get_resources_list($resources,'extra');
            $html .= html_writer::end_div();
        }
        //extra section end
        $html .= html_writer::end_div();
        //block content end

        $content->text = $html;

        //$content->footer = $this->get_add_activities_resources_link();
    }

    public static function get_assignment_subheadings($assessment_information,$that,$config){
        $html = '';
        if(!is_array($config->subheadings_title)) {
            if ($config->subheadings_background) {
                $html .= html_writer::tag('h3', $config->subheadings_title, array(
                    'class' => 'subheadings_title',
                    'style' => 'background:' . $config->subheadings_background
                ));
            } else {
                $html .= html_writer::start_tag('p', array('class' => 'modassignment'));
                $html .= html_writer::tag('strong', $config->subheadings_title);
                $html .= html_writer::end_tag('p');
            }
            if ($config->subheadings_text['text']) {
                $html .= html_writer::tag('p', $config->subheadings_text['text'],
                    array('class' => 'subheadings_text')
                );
            }
            //list subheadings resources
            if($that->coursepage->user_is_editing()){
                $resources = $assessment_information->get_course_resources('subheadings',0);
            } else {
                $resources = $assessment_information->get_course_resources('subheadings');
            }
            $html .= $that->get_resources_list($resources,'subheadings');
        } else {
            $totalSubHeadings = sizeof($config->subheadings_title);
            for ($i = 0; $i<$totalSubHeadings; $i++) {
                if ($config->subheadings_background[$i]) {
                    $html .= html_writer::tag('h3', $config->subheadings_title[$i], array(
                        'class' => 'subheadings_title',
                        'style' => 'background:' . $config->subheadings_background[$i]
                    ));
                } else {
                    $html .= html_writer::start_tag('p', array('class' => 'modassignment'));
                    $html .= html_writer::tag('strong', $config->subheadings_title[$i]);
                    $html .= html_writer::end_tag('p');
                }
                if ($config->subheadings_text[$i]['text']) {
                    $html .= html_writer::tag('p', $config->subheadings_text[$i]['text'],
                        array('class' => 'subheadings_text')
                    );
                }
                $subheadings='subheadings';
                if ($i!=0){
                    $subheadings.='-'.$i;
                }
                //list subheadings resources
                if($that->coursepage->user_is_editing()){
                    $resources = $assessment_information->get_course_resources($subheadings,0);
                } else {
                    $resources = $assessment_information->get_course_resources($subheadings);
                }
                $html .= $that->get_resources_list($resources,$subheadings);
            }
        }
        return $html;
    }
    public function renderAssign($instanceid,$currentuserroleid,$cmid,$html){
                    global $DB,$COURSE,$USER,$CFG;

                    $sqldue='select duedate from {assign} where course='.$COURSE->id.' and id= '.$instanceid;
                     $arrdue=$DB->get_record_sql($sqldue);

                   $sqlid="select id from {grade_items} where itemmodule='assign' and courseid= ".$COURSE->id." and iteminstance= ".$instanceid;
                    $execid=$DB->get_record_sql($sqlid);
                       $gradeitemid=$execid->id;


                    if($arrdue->duedate != 0){
                        $timestamp=$arrdue->duedate;
                        $date = date('d-m-Y H:i', $timestamp);
                        
                         $currentdate=time();
                         
                        if($currentdate>$timestamp){
                            
                            // if (is_siteadmin() || $currentuserroleid == 4){
                                
                            //  $sqlsubmitcount='select count(*) as count from {assign_submission} where assignment= '.$instanceid.' and status= "submitted"';
                            //     $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
                            //     /*graded count*/
                                 
                            //     if($gradeitemid != ""){
                                    
                            //         //* graded count*/
                            //         $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
                            //         $exec1=$DB->get_record_sql($sqlgraded);
                            //         $gradedcount=$exec1->count;


                            //     }

                            //     /*graded count*/
                            //     if($arrsubcount->count != ""){
                            //         $submitcount=$arrsubcount->count;

                            //         $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
                            //         $html.='<label style="float:right;color:blue;font-size: smaller;">'.
                            //         ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';

                            //         $html.='<label class="due-date badge m-1 badge-danger"  id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                     
                            //     }

                            // }
                            if($currentuserroleid == 5){
                               
                                $sqlsubmit='select status,timemodified from {assign_submission} where userid= '.$USER->id.' and assignment= '.$instanceid;
                                $arrsubmit=$DB->get_record_sql($sqlsubmit);
                               
                                if( !isset($arrsubmit->status) || $arrsubmit->status == 'new'){

                                    
                                     $html.='<label class="due-date badge m-1" data-toggle="tooltip" title ="Overdue" id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                     $html.='<label class="due-date badge m-1 badge-danger" data-toggle="tooltip" title ="Overdue" id="late_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Late</label>';


                                }
                                else if( isset($arrsubmit->status) && $arrsubmit->status == 'submitted'){

                                    // $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "assign"';
                                    // $gradeshow=$DB->get_record_sql($sqlgradeshow);
                                    // if($gradeshow->gradevisible == 1){
                                        
                                        $sqlgrade='select finalgrade,feedback,hidden from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
                                        $grade=$DB->get_record_sql($sqlgrade);
                                        // $grade=$exec->finalgrade;
                                    // }

                                    $html.='<label class="due-date badge m-1 "  id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                    
                                     $html.='<label class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;border:1px solid #ddd;">Submitted '.date("d-m-Y H:i",$arrsubmit->timemodified).'</label>';

                                     if($arrsubmit->timemodified >$arrdue->duedate ){
                                        $late=$arrsubmit->timemodified-$arrdue->duedate;
                                        $days=intval($late/86400);
                                        $remain=$late%86400;
                                        $hours=intval($remain/3600);
                                        $remain=$remain%3600;
                                        $mins=intval($remain/60);
                                        $secs=$remain%60;

                                        if ($secs>=0) $timestring = "0 min".$secs." sec";
                                        if ($mins>0) $timestring = $mins." min".$secs." sec";
                                        if ($hours>0) $timestring = $hours." hour".$mins." min";
                                        if ($days>0) $timestring = $days." day";

                                        

                                        $html.='<label class="due-date badge m-1 badge-danger" style="border-radius: .25rem;padding:5px;margin-right:5px;">'. $timestring .' Late </label>';

                                     }

                                    if(($grade->feedback != null || $grade->finalgrade != null) && $grade->hidden != 1 ){

                                     $html.='<a class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;text-align:center;color:black;border:1px solid #ddd;" href="'.$CFG->wwwroot.'/local/qmul_dashboard/index.php?cid='.$COURSE->id.'">Grade and Feedback</a>';
                                    }
                                }
                                else if(isset($arrsubmit->status) && $arrsubmit->status == 'draft'){

                                    $html.='<label class="due-date badge m-1 "  id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                    $html.='<label class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;border:1px solid #ddd;">Draft Submitted '.date("d-m-Y H:i",$arrsubmit->timemodified).'</label>';

                                    $html.='<label class="due-date badge m-1 badge-danger" data-toggle="tooltip" title ="Overdue" id="late_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Late</label>';


                                }
                            
                            }

                        }
                        else{
                           
                             // if (is_siteadmin() || $currentuserroleid == 4){
                                 
                             //             $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style=" background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                   
                             // }

                                if($currentuserroleid == 5 ){
                                        $sqlsubmit='select status,timemodified from {assign_submission} where userid= '.$USER->id.' and assignment= '.$instanceid;
                                        $arrsubmit=$DB->get_record_sql($sqlsubmit);
                                        
                                        if( isset($arrsubmit->status) && $arrsubmit->status == 'submitted'){
                                             // $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "assign"';
                                            // $gradeshow=$DB->get_record_sql($sqlgradeshow);
                                            // if($gradeshow->gradevisible == 1){
                                                
                                                $sqlgrade='select finalgrade,feedback, hidden from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
                                                $grade=$DB->get_record_sql($sqlgrade);
                                                // $grade=$exec->finalgrade;
                                            // }
                                            $html.='<label class="due-date badge m-1 " id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                            
                                             $html.='<label class="due-date badge m-1 " style="border:1px solid #ddd;border-radius: .25rem;padding:5px;margin-right:5px;color:black;">Submitted '.date("d-m-Y H:i",$arrsubmit->timemodified).'</label>';

                                            if(($grade->feedback != null || $grade->finalgrade != null) && $grade->hidden != 1){

                                             $html.='<a class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;text-align:center;color:black;border:1px solid #ddd;" href="'.$CFG->wwwroot.'/local/qmul_dashboard/index.php?cid='.$COURSE->id.'">Grade and Feedback</a>';
                                            }

                                    }
                                    else if(isset($arrsubmit->status) && $arrsubmit->status == 'draft'){

                                        $html.='<label class="due-date badge m-1 " id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                        $html.='<label class="due-date badge m-1 " style="border:1px solid #ddd;border-radius: .25rem;padding:5px;margin-right:5px;color:black;"> Draft Submitted '.date("d-m-Y H:i",$arrsubmit->timemodified).'</label>';

                                    }
                                     else{
                                       $html.='<label class="due-date badge m-1 " id="due_'.$instanceid.'" style="
                                            border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                     }
                                        
                                }

                        }
                         
                         
                    }
                    else if($arrdue->duedate == 0){
                        if($currentuserroleid == 5 ){
                               
                              $sqlsubmit='select status,timemodified from {assign_submission} where userid= '.$USER->id.' and assignment= '.$instanceid;
                             $arrsubmit=$DB->get_record_sql($sqlsubmit);
                             if( isset($arrsubmit->status) && $arrsubmit->status == 'submitted'){

                                    // $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "assign"';
                                    // $gradeshow=$DB->get_record_sql($sqlgradeshow);
                                    // if($gradeshow->gradevisible == 1){
                                        
                                        $sqlgrade='select finalgrade,feedback,hidden from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
                                        $grade=$DB->get_record_sql($sqlgrade);
                                        // $grade=$exec->finalgrade;
                                    // }

                                    $html.='<label class="due-date badge m-1 badge-danger" style="border:1px solid #ddd;border-radius: .25rem;padding:5px;margin-right:5px;color:black;">Submitted'.date("d-m-Y H:i",$arrsubmit->timemodified).'</label>';

                                    if(($grade->finalgrade != null || $grade->feedback != null) && $grade->hidden != 1){

                                    $html.='<a class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;text-align:center;color:black;border:1px solid #ddd;" href="'.$CFG->wwwroot.'/local/qmul_dashboard/index.php?cid='.$COURSE->id.'">Grade and Feedback</a>';
                                     }

                                }

                         }
                        // if (is_siteadmin() || $currentuserroleid == 4){
                                
                        //      $sqlsubmitcount='select count(*) as count from {assign_submission} where assignment= '.$instanceid.' and status= "submitted"';
                        //         $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
                        //         /*graded count*/
                                 
                        //         if($gradeitemid != ""){
                                    
                        //             //* graded count*/
                        //             $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
                        //             $exec1=$DB->get_record_sql($sqlgraded);
                        //             $gradedcount=$exec1->count;


                        //         }

                        //         /*graded count*/
                        //         if($arrsubcount->count != ""){
                        //             $submitcount=$arrsubcount->count;

                        //             $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
                        //             $html.='<label style="float:right;color:blue;font-size: smaller;">'.
                        //             ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';
                        //         }
                        // }
                    }
                    return $html;
                   
                }
    // ends

    public function get_resources_list($resources, $section){
       global $COURSE,$DB,$USER,$CFG,$PAGE;

        $html = '';
        // $PAGE->requires->jquery();
        $html .= html_writer::start_tag('ul', array('class'=>'resource-list',
            'id'=>$section));
            // for section52
        $sql='update {course_sections} set name="Generated by Assessment Information block" where section= 52 and course = '.$COURSE->id;
        $result=$DB->execute($sql);
            ?>
           
           
                        <script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
                       <script type="text/javascript">
                           jQuery(document).ready(function($){
                            
                                
                            setInterval(function(){
                                var course=<?php echo $COURSE->id ; ?>;
                                $.ajax({
                                    url: '../blocks/assessment_information/ajax.php',
                                    type:'POST',
                                    data:{'request':'regulartime','course':course},
                                    success: function(data){
                                        if(data != ""){

                                           if($('#section52_frmDB').length == 0){
                                                $('<input>').attr({
                                                type: 'hidden',
                                                id: 'section52_frmDB',
                                                name: 'section52_frmDB'
                                            }).appendTo('body');
                                           }
                                            $('#section52_frmDB').val(data);
                                        }
                                        else{
                                            $('<input>').attr({
                                                type: 'hidden',
                                                id: 'section52_frmDB',
                                                name: 'section52_frmDB'
                                            }).appendTo('body');
                                        }
                                    }

                                });
                             }, 1000);
                            
                           });
                       </script>
                          
                       <?php
            // functions 

                       //functions end
        foreach ($resources as $resource) {
        $cmid=$resource->itemid;//course module id
        $sql='select deletioninprogress,visible from {course_modules} where id='.$cmid;
        $deletion=$DB->get_record_sql($sql);

		// check if that activity is hidden from course

        if(isset ($deletion) && $deletion->visible != ""){
                $resource->visible = $deletion->visible;
                $record = new StdClass();
                $record->id = $resource->id;
                $record->visible = $deletion->visible;

                $result = $DB->update_record('block_assessment_information', $record);
            }		
		
        if(isset($deletion) && $deletion->deletioninprogress != 1 ){
               
        $sql_access="select gm.userid,cm.module from {course_modules} cm 
                    JOIN {modules} mo on mo.id = cm.module and mo.name = 'assign'
                    join {groupings} gp on gp.id = cm.groupingid
                    join {groupings_groups} gg on gg.groupingid = gp.id
                    join {groups} g on g.id = gg.groupid
                    join {groups_members} gm on gm.groupid = g.id
                    join {course} co on co.id = cm.course
                    join {context} cx on cx.instanceid = cm.course and cx.contextlevel = 50
                    join {context} cx1 on cx1.id = cx.id or (cx1.instanceid = cm.id and cx1.contextlevel = 70)
                    JOIN {role_assignments} ra on ra.contextid = cx1.id and ra.userid = gm.userid
                    join {role} ro on ro.id = ra.roleid
                    where cm.id = $cmid";
                    $userlist=$DB->get_records_sql($sql_access);

                    $userarr = array();
                    if(isset($userlist)){
                        foreach($userlist as $userid){
                            if(isset($userid->userid)){
                                $module=$userid->module;
                                array_push($userarr,$userid->userid);
                            }
                        }
                    }



            if(!array_key_exists($resource->itemid, $this->modinfo->cms)) {
                continue;
            }
            $visibleclass = $resource->visible ? ' hide-link' : ' show-link';
            $html .= html_writer::start_div('resource clearfix'.$visibleclass);
            if($this->coursepage->user_is_editing()){
                $moveicon = html_writer::empty_tag('img',
                        array('src' => $this->image_url('t/move')->out(false),
                            'alt' => 'move',
                            'title' => get_string('move')));
                $moveurl = new moodle_url($this->page->url, array(
                    'moveresource' => 1, 'itemid' => $resource->id
                ));
                $moveurl = html_writer::link($moveurl, $moveicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'move'));
            }
            $mod = $this->modinfo->cms[$resource->itemid];
            $resource_link  = html_writer::link(
                new moodle_url($resource->url),
                $mod->get_formatted_name()
            );


            if(isset($userlist) && count($userarr) > 0){
                // echo 'first';
                  if(in_array($USER->id, $userarr)){  
                    $html .= html_writer::tag('li', $resource_link, array(
                        'id'=>$resource->id,
                        'style'=>'background:url('.$mod->get_icon_url().') no-repeat'
                    ));
                }
               
            }
            else if(isset($userlist) && count($userarr) == 0){
                // echo 'second'."<br>";
                    $html .= html_writer::tag('li', $resource_link, array(
                        'id'=>$resource->id,
                        'style'=>'background:url('.$mod->get_icon_url().') no-repeat'
                    ));
                
            }
            
           


            


            if($this->coursepage->user_is_editing()){
                $toggleclass = $resource->visible ? 'hide' : 'show';
                $html .= html_writer::link(
                    '#', ucfirst($toggleclass), array(
                        'title'=>ucfirst($toggleclass) .' Link',
                        'class'=>'remove-link',
                        'data-id'=>$resource->id,
                        'data-state'=>$resource->visible
                    )
                );
            }
             
            

           
                // CODE TO GET CURRENT USERS ROLE starts
           
            // $context = context_module::instance($COURSE->id);

           
           $sql_context = "SELECT id FROM {context} where contextlevel=50 and instanceid=".$COURSE->id;
            $context1=$DB->get_record_sql($sql_context);
            
            $currentuserroleid=0;
             //getting roleid for  teacher, editingteacher, course_admin, student,jp_student
            //Default roleids 
            //student = 5;
            //teacher = 4;
            //
            $sql= 'select id from {role} where shortname="student"';
            $exec=$DB->get_record_sql($sql);
            $student_roleid=$exec->id;// id=42
             $sql= 'select id from {role} where shortname="jp_student"';
            $exec=$DB->get_record_sql($sql);
            $jp_student_roleid=$exec->id;//id=43

            $sql= 'select id from {role} where shortname="course_admin"';
            $exec=$DB->get_record_sql($sql);
            $course_admin_roleid=$exec->id;// id=37

            $sql= 'select id from {role} where shortname="editingteacher"';
            $exec=$DB->get_record_sql($sql);
            $editingteacher_roleid=$exec->id;//id=38

            $sql= 'select id from {role} where shortname="teacher"';
            $exec=$DB->get_record_sql($sql);
            $teacher_roleid=$exec->id;// id=39

            // echo $student_roleid. " ".$jp_student_roleid." ".$course_admin_roleid." ".$editingteacher_roleid." ".$teacher_roleid;



            $sqlrole ='select roleid from {role_assignments} where contextid= '. $context1->id.' and userid= '.$USER->id;
            $arrrole=$DB->get_records_sql($sqlrole);
            // under mentioned checks are for a user having dual roles(teacher and student) 
            $teacher_check=0;
            $student_check=0;
            if(isset($arrrole)){
                
                foreach ($arrrole as $arole){
                    if($arole->roleid == $teacher_roleid ||$arole->roleid == $editingteacher_roleid || $arole->roleid == $course_admin_roleid ){
                        $currentuserroleid=4;
                        $teacher_check=1;
                        if($student_check == 1){
                            break;
                        }

                    }
                    else if($arole->roleid == $jp_student_roleid ||$arole->roleid == $student_roleid ){
                        $currentuserroleid=5;
                        $student_check=1;
                        if($teacher_check == 1){
                            $currentuserroleid=4;
                            break;
                        }

                    }

                }

            }
            
            // CODE TO GET CURRENT USERS ROLE ends
             $sqltotalusers="select count(*) as count from {role_assignments} where contextid=$context1->id and roleid = $student_roleid";

            

            // CODE TO GET CURRENT USERS ROLE starts
            
            // $context = context_module::instance($COURSE->id);

             
           /* $sql_context = "SELECT id FROM {context} where contextlevel=50 and instanceid=".$COURSE->id;
            $context1=$DB->get_record_sql($sql_context);
            $currentuserroleid=0;

            $sqlrole ='select roleid from {role_assignments} where contextid= '. $context1->id.' and userid= '.$USER->id;
            $arrrole=$DB->get_record_sql($sqlrole);
            if(isset($arrrole->roleid)){
                $currentuserroleid=$arrrole->roleid;
            }

            $sqltotalusers='select count(*) as count from {role_assignments} where contextid='.$context1->id .' and roleid=5';*/
            // CODE TO GET CURRENT USERS ROLE ends
            
            
            $arr_totalusers=$DB->get_record_sql($sqltotalusers);
            $total_users=$arr_totalusers->count;
            
            // echo "<pre>";
            // print_r($resource);
            
            $sqlinstanceid='select instance from {course_modules} where id= '.$cmid;
            $instanceid=$DB->get_record_sql($sqlinstanceid);
            $instanceid=$instanceid->instance;
       

            if($resource->mtable== "assign"){
                

                // for assignments//
            if(isset($userlist) && count($userarr) >0 ){
                if(in_array($USER->id, $userarr)){

                     $html =$this->renderAssign($instanceid,$currentuserroleid,$cmid,$html);
                    
                }
            } // if count >0

            if(isset($userlist) && count($userarr) == 0 ){

               $html =$this->renderAssign($instanceid,$currentuserroleid,$cmid,$html);
            } // if count = 0 
            

            }

            
            if($resource->mtable== "quiz"){

             $sqldue='select timeclose from {quiz} where course='.$COURSE->id.' and id= '.$instanceid;
             $arrdue=$DB->get_record_sql($sqldue);

                $sqlid="select id from {grade_items} where itemmodule='quiz' and courseid= ".$COURSE->id." and iteminstance= ".$instanceid;
                $execid=$DB->get_record_sql($sqlid);
                   $gradeitemid=$execid->id;

                if($arrdue->timeclose != 0){
                    $timestamp=$arrdue->timeclose;
                    $date = date('d-m-Y H:i', $timestamp);
                    
                     $currentdate=time();
                    if($currentdate>$timestamp){
                        
                        // if (is_siteadmin() || $currentuserroleid == 4){
                            
                        //  $sqlsubmitcount='select count(DISTINCT userid) as count FROM {quiz_attempts} where quiz= '.$instanceid.' and state= "finished" ';
                        //     $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
                        //     /*graded count*/
                             
                        //     if($gradeitemid!= ""){
                                
                        //         //* graded count*/
                        //         $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
                        //         $exec1=$DB->get_record_sql($sqlgraded);
                        //         $gradedcount=$exec1->count;
                                

                        //     }

                        //     /*graded count*/
                        //     if($arrsubcount->count != ""){
                        //         $submitcount=$arrsubcount->count;

                        //         $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
                        //         $html.='<label style="float:right;color:blue;font-size: smaller;">'.
                        //         ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';

                        //         $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                 
                        //     }

                        // }
                         if($currentuserroleid == 5){
                           
                             $sqlsubmit='select * FROM {quiz_attempts}
                         where quiz= '.$instanceid.' and userid= '.$USER->id.' order by timemodified desc LIMIT 1';
                             $arrsubmit=$DB->get_record_sql($sqlsubmit);
                           
                            if( !isset($arrsubmit->state) || $arrsubmit->state == 'inprogress'){
                                
                                $html.='<label class="due-date badge m-1" data-toggle="tooltip" title ="Overdue" id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                $html.='<label class="due-date badge m-1 badge-danger" data-toggle="tooltip" title ="Overdue" id="late_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Late</label>';

                            }
                            else if( isset($arrsubmit->state) && $arrsubmit->state == 'finished'){
                                $grade="";
                                // $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "quiz"';
                                // $gradeshow=$DB->get_record_sql($sqlgradeshow);

                                // if($gradeshow->gradevisible == 1){
                                    
                                     $sqlgrade='select finalgrade,feedback,hidden from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
                                    $grade=$DB->get_record_sql($sqlgrade);
                                    // $grade=$exec->finalgrade;

                                // }

                                $html.='<label class="due-date badge m-1 " id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                
                                 $html.='<label class="due-date badge m-1 " style="border:1px solid #ddd;border-radius: .25rem;padding:5px;margin-right:5px;">Submitted '.date('d-m-Y H:i',$arrsubmit->timemodified).'</label>';

                                


                                if(($grade->finalgrade != null || $grade->feedback != null) && $grade->hidden != 1){
                                 $html.='<a class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;text-align:center;color:black;border:1px solid #ddd;" href="'.$CFG->wwwroot.'/local/qmul_dashboard/index.php?cid='.$COURSE->id.'">Grade and Feedback</a>';
                                }
                            }
                        
                        }

                    }
                    else {
                        // if (is_siteadmin() || $currentuserroleid == 4){
                        
                        //          $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="
                        //             background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                            
                        // }

                        if($currentuserroleid == 5){
                            $sqlsubmit='select * FROM {quiz_attempts}
                         where quiz= '.$instanceid.' and userid= '.$USER->id.' order by timemodified desc LIMIT 1';
                             $arrsubmit=$DB->get_record_sql($sqlsubmit);
                            
                            if( isset($arrsubmit->state) && $arrsubmit->state == 'finished'){
                                    $grade="";
                                // $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "quiz"';
                                // $gradeshow=$DB->get_record_sql($sqlgradeshow);
                                // if($gradeshow->gradevisible == 1){
                                    
                                     $sqlgrade='select finalgrade,feedback,hidden from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
                                    $grade=$DB->get_record_sql($sqlgrade);
                                    // $grade=$exec->finalgrade;
                                    
                                // }
                                 $html.='<label class="due-date badge m-1 " id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                
                                 $html.='<label class="due-date badge m-1 " style="border:1px solid #ddd;border-radius: .25rem;padding:5px;margin-right:5px;">Submitted '.date('d-m-Y H:i',$arrsubmit->timemodified).'</label>';


                               if(($grade->finalgrade != null || $grade->feedback != null) && $grade->hidden != 1){
                                 $html.='<a class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;text-align:center;color:black;border:1px solid #ddd;" href="'.$CFG->wwwroot.'/local/qmul_dashboard/index.php?cid='.$COURSE->id.'">Grade and Feedback</a>';
                                }
                            }
                             else{
                               $html.='<label class="due-date badge m-1" id="due_'.$instanceid.'" style="border:1px solid #ddd;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                             }
                            
                        }

                    }
                }
                else if($arrdue->timeclose == 0){
                    if($currentuserroleid == 5){
                           
                            $sqlsubmit='select * FROM {quiz_attempts}
                            where quiz= '.$instanceid.' and userid= '.$USER->id.' order by timemodified desc LIMIT 1';
                             $arrsubmit=$DB->get_record_sql($sqlsubmit);
                             if( isset($arrsubmit->state) && $arrsubmit->state == 'finished'){
                                $grade="";
                                // $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "quiz"';
                                // $gradeshow=$DB->get_record_sql($sqlgradeshow);
                                // if($gradeshow->gradevisible == 1){
                                    
                                    $sqlgrade='select finalgrade, feedback, hidden from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
                                    $grade=$DB->get_record_sql($sqlgrade);
                                    // $grade=$exec->finalgrade;
                                    
                                // }
                                 $html.='<label class="due-date badge m-1 " style="border:1px solid #ddd;border-radius: .25rem;padding:5px;margin-right:5px;">Submitted '.date('d-m-Y H:i',$arrsubmit->timemodified).'</label>';

                                if(($grade->finalgrade != null || $grade->feedback != null) && $grade->hidden != 1){
                                 $html.='<a class="due-date badge m-1 " style="border-radius: .25rem;padding:5px;margin-right:5px;text-align:center;color:black;border:1px solid #ddd;" href="'.$CFG->wwwroot.'/local/qmul_dashboard/index.php?cid='.$COURSE->id.'">Grade and Feedback</a>';
                                }

                            }
                    }
                    // if (is_siteadmin() || $currentuserroleid == 4){
                            
                    //      $sqlsubmitcount='select count(DISTINCT userid) as count FROM {quiz_attempts} where quiz= '.$instanceid.' and state= "finished" ';
                    //         $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
                    //         /*graded count*/
                             
                    //         if($gradeitemid!= ""){
                                
                    //             //* graded count*/
                    //             $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
                    //             $exec1=$DB->get_record_sql($sqlgraded);
                    //             $gradedcount=$exec1->count;
                                

                    //         }

                    //         /*graded count*/
                    //         if($arrsubcount->count != ""){
                    //             $submitcount=$arrsubcount->count;

                    //             $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
                    //             $html.='<label style="float:right;color:blue;font-size: smaller;">'.
                    //             ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';
                    //         }
                    // }

                }


            }
        /*blocked code*/
            // if($resource->mtable== "lesson"){
            //     $sqldue='select deadline from {lesson} where course='.$COURSE->id.' and id= '.$instanceid;
            //      $arrdue=$DB->get_record_sql($sqldue);

            //      $sqlid="select id from {grade_items} where itemmodule='lesson' and courseid= ".$COURSE->id." and iteminstance= ".$instanceid;
            //         $execid=$DB->get_record_sql($sqlid);
            //        $gradeitemid=$execid->id;

            //     if($arrdue->deadline != 0){
            //          $timestamp=$arrdue->deadline;
            //          $date = date('d-m-Y', $timestamp);
                    
            //          $currentdate=time();
               //          if($currentdate>$timestamp){
               //              if (is_siteadmin() || $currentuserroleid == 4){
                                
               //               $sqlsubmitcount='SELECT COUNT(DISTINCT userid) as count FROM {lesson_grades} WHERE lessonid= '.$instanceid;
               //                  $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
               //                  /*graded count*/
                                 
               //                  if($gradeitemid!= ""){
                                    
               //                      //* graded count*/
               //                      $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
               //                      $exec1=$DB->get_record_sql($sqlgraded);
               //                      $gradedcount=$exec1->count;


               //                  }

               //                  /*graded count*/
               //                  if($arrsubcount->count != ""){
               //                      $submitcount=$arrsubcount->count;

               //                      $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
               //                      $html.='<label style="float:right;color:blue;font-size: smaller;">'.
               //                      ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';

               //                      $html.='<label class="due-date badge m-1 badge-danger"  id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                     
               //                  }

               //              }
               //              else if($currentuserroleid == 5){
                               
               //                   $sqlsubmit='SELECT count(*) as count FROM {lesson_grades} where userid= '.$USER->id.' and lessonid= '.$instanceid;
               //                   $arrsubmit=$DB->get_record_sql($sqlsubmit);
                                 

               //                  if( $arrsubmit->count == 0){
                                    
               //                      $html.='<label class="due-date badge m-1 badge-danger" data-toggle="tooltip" title ="Overdue" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                    
                                    

               //                  }
               //                  else if( $arrsubmit->count == 1){
               //                      $grade="";
               //                       $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "lesson"';
               //                      $gradeshow=$DB->get_record_sql($sqlgradeshow);
               //                      if($gradeshow->gradevisible == 1){
                                        
               //                          $sqlgrade='select finalgrade from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
               //                          $exec=$DB->get_record_sql($sqlgrade);
               //                          $grade=$exec->finalgrade;
                                        
               //                      }

               //                      $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                    
               //                       $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';

               //                       if($gradeshow->gradevisible == 1){
               //                       $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#8FC33A;margin-right:5px;color:white;text-align:center">'.(isset($grade) && $grade >=0? round($grade,1): 'Not graded').'</label>';
               //                      }
               //                  }
                            
               //              }

               //          }
               //          else{
               //              if (is_siteadmin() || $currentuserroleid == 4){
                             
               //                      $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="
               //                          background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                
               //              }

               //              if($currentuserroleid == 5 && !is_siteadmin()){
               //                     $sqlsubmit='SELECT count(*) as count FROM {lesson_grades} where userid= '.$USER->id.' and lessonid= '.$instanceid;
               //                   $arrsubmit=$DB->get_record_sql($sqlsubmit);
                                
               //                  if( $arrsubmit->count == 1){

               //                      $grade="";
               //                       $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "lesson"';
               //                      $gradeshow=$DB->get_record_sql($sqlgradeshow);
               //                      if($gradeshow->gradevisible == 1){
                                        
               //                          $sqlgrade='select finalgrade from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
               //                          $exec=$DB->get_record_sql($sqlgrade);
               //                          $grade=$exec->finalgrade;
                                        
               //                      }

               //                       $html.='<label class="due-date badge m-1 badge-danger"  id="due_'.$instanceid.'" style="
               //                          background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                    
               //                       $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';

               //                        if($gradeshow->gradevisible == 1){
               //                       $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#8FC33A;margin-right:5px;color:white;text-align:center">'.(isset($grade) && $grade >=0? round($grade,1): 'Not graded').'</label>';
               //                      }
               //                  }
               //                  else{
               //                     $html.='<label  class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
               //                  }
               //              }


               //          }
            //     }
            //     else if($arrdue->deadline == 0){
            //          if($currentuserroleid == 5){
                               
               //                   $sqlsubmit='SELECT count(*) as count FROM {lesson_grades} where userid= '.$USER->id.' and lessonid= '.$instanceid;
               //                   $arrsubmit=$DB->get_record_sql($sqlsubmit);
               //                   if( $arrsubmit->count == 1){
               //                       $grade="";
               //                       $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "lesson"';
               //                       $gradeshow=$DB->get_record_sql($sqlgradeshow);
               //                      if($gradeshow->gradevisible == 1){
                                        
               //                          $sqlgrade='select finalgrade from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
               //                          $exec=$DB->get_record_sql($sqlgrade);
               //                          $grade=$exec->finalgrade;
                                        
               //                      }

               //                      $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';

                  //                       if($gradeshow->gradevisible == 1){
                  //                        $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#8FC33A;margin-right:5px;color:white;text-align:center">'.(isset($grade) && $grade >=0? round($grade,1): 'Not graded').'</label>';
                  //                       }

               //                   }

               //          }
               //          if (is_siteadmin() || $currentuserroleid == 4){
                                
               //               $sqlsubmitcount='SELECT COUNT(DISTINCT userid) as count FROM {lesson_grades} WHERE lessonid= '.$instanceid;
               //                  $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
               //                  /*graded count*/
                                 
               //                  if($gradeitemid!= ""){
                                    
               //                      //* graded count*/
               //                      $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
               //                      $exec1=$DB->get_record_sql($sqlgraded);
               //                      $gradedcount=$exec1->count;


               //                  }

               //                  /*graded count*/
               //                  if($arrsubcount->count != ""){
               //                      $submitcount=$arrsubcount->count;

               //                      $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
               //                      $html.='<label style="float:right;color:blue;font-size: smaller;">'.
               //                      ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';
               //                  }
               //          }
            //     }

            // }
            // if($resource->mtable == "workshop"){

            //     $sqldue="select submissionend from {workshop} where course= ".$COURSE->id.' and id= '.$instanceid;
            //     $arrdue=$DB->get_record_sql($sqldue);
               
            //     if($arrdue->submissionend != 0){
            //          $timestamp=$arrdue->submissionend;
            //             $date = date('d-m-Y', $timestamp);
                    
            //          $currentdate=time();
            //             if($currentdate>$timestamp){
            //                 if (is_siteadmin() || $currentuserroleid == 4){
            //                     $sqlsubcount='select count(DISTINCT authorid)  as count from {workshop_submissions} where workshopid= '.$instanceid;
            //                     $arrsubcount=$DB->get_record_sql($sqlsubcount);
            //                     if($arrsubcount->count != ""){
            //                         $submitcount=$arrsubcount->count;
            //                          $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
                                       

            //                         $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
            //                     }
            //                 }
            //                 else if($currentuserroleid == 5){
            //                     $sqlsubmit='select count(DISTINCT authorid) as count from {workshop_submissions} where workshopid= '.$instanceid .' and authorid= '.$USER->id;
            //                     $arrsubmit=$DB->get_record_sql($sqlsubmit);
            //                     if($arrsubmit->count == 1){

            //                          $html.='<label  class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                
            //                         $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';
            //                     }
            //                     else{
            //                          $html.='<label class="due-date badge m-1 badge-danger"  data-toggle="tooltip" title ="Overdue" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
            //                     }

            //                 }

            //             }
            //             else{
            //                 if (is_siteadmin() || $currentuserroleid == 4){
            //                     $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="
            //                             background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

            //                   }
            //                   else if($currentuserroleid == 5){
            //                      $sqlsubmit='select count(DISTINCT authorid) as count from {workshop_submissions} where workshopid= '.$instanceid .' and authorid= '.$USER->id;
            //                     $arrsubmit=$DB->get_record_sql($sqlsubmit);
            //                     if($arrsubmit->count == 1){

            //                       $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="                    background-color: #F7882F;;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                
            //                         $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';
            //                     }
            //                     else{
            //                          $html.='<label class="due-date badge m-1 badge-danger"  id="due_'.$instanceid.'" style="                  background-color: #F7882F;;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
            //                     }

                                
            //                   }

            //             }
            //     }
            //     else if($arrdue->submissionend == 0){
            //          if($currentuserroleid == 5){
            //                     $sqlsubmit='select count(DISTINCT authorid) as count from {workshop_submissions} where workshopid= '.$instanceid .' and authorid= '.$USER->id;
            //                     $arrsubmit=$DB->get_record_sql($sqlsubmit);
            //                     if($arrsubmit->count == 1){
            //                       $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';
            //                     }
            //             }

            //             if (is_siteadmin() || $currentuserroleid == 4){
            //                     $sqlsubcount='select count(DISTINCT authorid)  as count from {workshop_submissions} where workshopid= '.$instanceid;
            //                     $arrsubcount=$DB->get_record_sql($sqlsubcount);
            //                     if($arrsubcount->count != ""){
            //                         $submitcount=$arrsubcount->count;
            //                          $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
            //                     }
            //             }

            //     }
            // }
            // if($resource->mtable == "kalvidassign"){
            //  // for kalvidassign//

            //     $sqldue='select timedue from {kalvidassign} where course='.$COURSE->id.' and id= '.$instanceid;
            //     $arrdue=$DB->get_record_sql($sqldue);

            //      $sqlid="select id from {grade_items} where itemmodule='kalvidassign' and courseid= ".$COURSE->id." and iteminstance= ".$instanceid;
            //     $execid=$DB->get_record_sql($sqlid);
            //     $gradeitemid=$execid->id;


            //     if($arrdue->timedue != 0){
            //         $timestamp=$arrdue->timedue;
            //         $date = date('d-m-Y', $timestamp);
                    
            //          $currentdate=time();
                     
            //         if($currentdate>$timestamp){
                        
            //             if (is_siteadmin() || $currentuserroleid == 4){
                            
            //              $sqlsubmitcount='select count(*) as count from {kalvidassign_submission} where vidassignid= '.$instanceid;
            //                 $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
            //                 /*graded count*/
                             
            //                 if($gradeitemid != ""){
                                
            //                     //* graded count*/
            //                     $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
            //                     $exec1=$DB->get_record_sql($sqlgraded);
            //                     $gradedcount=$exec1->count;


            //                 }

            //                 /*graded count*/
            //                 if($arrsubcount->count != ""){
            //                     $submitcount=$arrsubcount->count;

            //                     $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
            //                     $html.='<label style="float:right;color:blue;font-size: smaller;">'.
            //                     ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';

            //                     $html.='<label class="due-date badge m-1 badge-danger"  id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                                 
            //                 }

            //             }
            //             else if($currentuserroleid == 5){
                           
            //                   $sqlsubmit='select count(*) as count from {kalvidassign_submission} where userid= '.$USER->id.' and vidassignid= '.$instanceid;
            //                  $arrsubmit=$DB->get_record_sql($sqlsubmit);
                           
            //                 if( $arrsubmit->count == 0){
                                
            //                     $html.='<label class="due-date badge m-1 badge-danger" data-toggle="tooltip" title ="Overdue" id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                
                                

            //                 }
            //                  else if( $arrsubmit->count == 1){

            //                     $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "kalvidassign"';
            //                     $gradeshow=$DB->get_record_sql($sqlgradeshow);
            //                     if($gradeshow->gradevisible == 1){
                                    
            //                         $sqlgrade='select finalgrade from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
            //                         $exec=$DB->get_record_sql($sqlgrade);
            //                         $grade=$exec->finalgrade;
            //                     }

            //                     $html.='<label class="due-date badge m-1 badge-danger"  id="due_'.$instanceid.'" style="float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                
            //                      $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';
            //                     if($gradeshow->gradevisible == 1){

            //                      $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#8FC33A;margin-right:5px;color:white;text-align:center">'.(isset($grade) && $grade >=0? round($grade,1): 'Not graded').'</label>';
            //                      }
            //                  }
                        
            //             }

            //         }
            //         else{
                       
            //              if (is_siteadmin() || $currentuserroleid == 4){
                             
            //                          $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style=" background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';

                               
            //              }

            //                 if($currentuserroleid == 5 && !is_siteadmin()){
            //                           $sqlsubmit='select count(*) as count from {kalvidassign_submission} where userid= '.$USER->id.' and vidassignid= '.$instanceid;
            //                          $arrsubmit=$DB->get_record_sql($sqlsubmit);
                                    
            //                          if( isset($arrsubmit->status) && $arrsubmit->status == 'submitted'){
            //                              $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "kalvidassign"';
            //                             $gradeshow=$DB->get_record_sql($sqlgradeshow);
            //                             if($gradeshow->gradevisible == 1){
                                            
            //                                 $sqlgrade='select finalgrade from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
            //                                 $exec=$DB->get_record_sql($sqlgrade);
            //                                 $grade=$exec->finalgrade;
            //                             }
            //                              $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
                                        
            //                              $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;font-weight:500;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';

            //                             if($gradeshow->gradevisible == 1){

            //                              $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#8FC33A;margin-right:5px;color:white;text-align:center">'.(isset($grade) && $grade >=0? round($grade,1): 'Not graded').'</label>';
            //                             }

            //                          }
            //                          else{
            //                            $html.='<label class="due-date badge m-1 badge-danger" id="due_'.$instanceid.'" style="
            //                                 background-color: #F7882F;float:right;border-radius: .25rem;padding:5px">Due '.$date.'</label>';
            //                          }
                                    
            //                 }

            //         }
                     
            //     }
            //  else if($arrdue->timedue == 0){
            //      if($currentuserroleid == 5){
                       
            //          $sqlsubmit='select count(*) as count from {kalvidassign_submission} where userid= '.$USER->id.' and vidassignid= '.$instanceid;
            //          $arrsubmit=$DB->get_record_sql($sqlsubmit);
            //          if( $arrsubmit->count == 1){

            //                 $sqlgradeshow='select gradevisible from {block_assessment_information} where itemid= '.$cmid.' and mtable = "kalvidassign"';
            //                 $gradeshow=$DB->get_record_sql($sqlgradeshow);
            //                 if($gradeshow->gradevisible == 1){
                                
            //                     $sqlgrade='select finalgrade from {grade_grades} where userid= '.$USER->id.' and itemid= '.$gradeitemid;
            //                     $exec=$DB->get_record_sql($sqlgrade);
            //                     $grade=$exec->finalgrade;
            //                 }

            //                  $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#005A9A;margin-right:5px;color:white;">Submitted</label>';
            //                 if($gradeshow->gradevisible == 1){

            //                  $html.='<label class="due-date badge m-1 badge-danger" style="float:right;border-radius: .25rem;padding:5px;background:#8FC33A;margin-right:5px;color:white;text-align:center">'.(isset($grade) && $grade >=0? round($grade,1): 'Not graded').'</label>';
            //                 }
            //          }
            //      }

            //      if (is_siteadmin() || $currentuserroleid == 4){
                            
            //              $sqlsubmitcount='select count(*) as count from {kalvidassign_submission} where vidassignid= '.$instanceid;
            //                 $arrsubcount=$DB->get_record_sql($sqlsubmitcount);
            //                 /*graded count*/
                             
            //                 if($gradeitemid != ""){
                                
            //                     //* graded count*/
            //                     $sqlgraded='select count(*) as count from {grade_grades} where finalgrade >= 0 and itemid= '.$gradeitemid;
            //                     $exec1=$DB->get_record_sql($sqlgraded);
            //                     $gradedcount=$exec1->count;


            //                 }

            //                 /*graded count*/
            //                 if($arrsubcount->count != ""){
            //                     $submitcount=$arrsubcount->count;

            //                     $html.='<label style="float:right;color:blue;font-size: smaller;">'.$submitcount.' of '.$total_users.' submitted</label><br>';
            //                     $html.='<label style="float:right;color:blue;font-size: smaller;">'.
            //                     ($gradedcount != 0 ?($submitcount-$gradedcount):'All').' Ungraded</label><br>';
            //                 }
            //         }
            //  }
       

            // }
        /*blocked code*/
        
    
            
            


            $html .= html_writer::end_div();
        
        }// isset deletion finishes
    }// foreach resources finishes
        $html .= html_writer::end_tag('ul');

        return $html;
    }

    //This is a function copied from course renderer
    function get_activity_chooser_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $CFG;


        $vertical = !empty($displayoptions['inblock']);

        // check to see if user can add menus and there are modules to add
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
                || !$this->page->user_is_editing()
                || !($modnames = get_module_types_names()) || empty($modnames)) {
            return '';
        }

        // Retrieve all modules with associated metadata
        $modules = get_module_metadata($course, $modnames, $sectionreturn);
        $urlparams = array('section' => $section);

        // We'll sort resources and activities into two lists
        $activities = array(MOD_CLASS_ACTIVITY => array(), MOD_CLASS_RESOURCE => array());

        foreach ($modules as $module) {
            if (isset($module->types)) {
                // This module has a subtype
                // NOTE: this is legacy stuff, module subtypes are very strongly discouraged!!
                $subtypes = array();
                foreach ($module->types as $subtype) {
                    $link = $subtype->link->out(true, $urlparams);
                    $subtypes[$link] = $subtype->title;
                }

                // Sort module subtypes into the list
                $activityclass = MOD_CLASS_ACTIVITY;
                if ($module->archetype == MOD_CLASS_RESOURCE) {
                    $activityclass = MOD_CLASS_RESOURCE;
                }
                if (!empty($module->title)) {
                    // This grouping has a name
                    $activities[$activityclass][] = array($module->title => $subtypes);
                } else {
                    // This grouping does not have a name
                    $activities[$activityclass] = array_merge($activities[$activityclass], $subtypes);
                }
            } else {
                // This module has no subtypes
                $activityclass = MOD_CLASS_ACTIVITY;
                if ($module->archetype == MOD_ARCHETYPE_RESOURCE) {
                    $activityclass = MOD_CLASS_RESOURCE;
                } else if ($module->archetype === MOD_ARCHETYPE_SYSTEM) {
                    // System modules cannot be added by user, do not add to dropdown
                    continue;
                }
                $link = $module->link->out(true, $urlparams);
                $activities[$activityclass][$link] = $module->title;
            }
        }

        $straddactivity = get_string('addactivity');
        $straddresource = get_string('addresource');
        $sectionname = get_section_name($course, $section);
        $strresourcelabel = get_string('addresourcetosection', null, $sectionname);
        $stractivitylabel = get_string('addactivitytosection', null, $sectionname);

        $output = html_writer::start_tag('div', array('class' => 'section_add_menus', 'id' => 'add_menus-section-' . $section));

        if (!$vertical) {
            $output .= html_writer::start_tag('div', array('class' => 'horizontal'));
        }

        if (!empty($activities[MOD_CLASS_RESOURCE])) {
            $select = new url_select($activities[MOD_CLASS_RESOURCE], '', array(''=>$straddresource), "ressection$section");
            $select->set_help_icon('resources');
            $select->set_label($strresourcelabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!empty($activities[MOD_CLASS_ACTIVITY])) {
            $select = new url_select($activities[MOD_CLASS_ACTIVITY], '', array(''=>$straddactivity), "section$section");
            $select->set_help_icon('activities');
            $select->set_label($stractivitylabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!$vertical) {
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div');

        if (course_ajax_enabled($course) && $course->id == $this->page->course->id) {
            // modchooser can be added only for the current course set on the page!
            $straddeither = get_string('addresourceoractivity');
            // The module chooser link
            $modchooser = html_writer::start_tag('div', array('class' => 'mdl-right'));
            $modchooser.= html_writer::start_tag('div', array('class' => 'section-modchooser'));
            $icon = $this->output->pix_icon('t/add', '');
            $span = html_writer::tag('span', $straddeither, array('class' => 'section-modchooser-text','id'=>'block_assess'));
            $modchooser .= html_writer::tag('span', $icon . $span, array('class' => 'section-assessment-modchooser-link'));
            $modchooser.= html_writer::end_tag('div');
            $modchooser.= html_writer::end_tag('div');

            // Wrap the normal output in a noscript div
            $usemodchooser = get_user_preferences('usemodchooser', $CFG->modchooserdefault);
            if ($usemodchooser) {
                $output = html_writer::tag('div', $output, array('class' => 'hiddenifjs addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'visibleifjs addresourcemodchooser'));
            } else {
                // If the module chooser is disabled, we need to ensure that the dropdowns are shown even if javascript is disabled
                $output = html_writer::tag('div', $output, array('class' => 'show addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'hide addresourcemodchooser'));
            }
            $courserenderer = $this->page->get_renderer('core','course');
            $output = $courserenderer->course_modchooser($modules, $course) . $modchooser . $output;
        }

        return $output;
    }
    function premix_resources_settings($courseid){
        global $DB, $PAGE;
        $html = '';
        $html .= html_writer::start_div('premix-resourses-wrap');
        $html .= html_writer::tag('h2',get_string('premix_resourses_heading','block_assessment_information'));
        if (!$courseid) {
            $html .= html_writer::tag('p',get_string('coursenotdefined','block_assessment_information'));
        } else {
            $course = $DB->get_record('course', array('id'=>$courseid));
            course_create_sections_if_missing($course,TOPIC_ZERO_SECTION);
            $section = $DB->get_record('course_sections', array('section'=>TOPIC_ZERO_SECTION, 'course'=>$courseid));
            $premixresources = $DB->count_records('course_modules',array(
                'course' => $course->id,
                'section' => $section->id
            ));
            if($premixresources){
                $html .= html_writer::tag('p',get_string('premixresourcesavailable','block_assessment_information',$course->fullname));
                $courserenderer = $PAGE->get_renderer('core','course');
                $html .= $courserenderer->course_section_cm_list($course, $section, 0);
            } else {
                $html .= html_writer::tag('p',get_string('premixresourcesnotavailable','block_assessment_information',$course->fullname));
            }
            $html .= $this->get_activity_chooser_control($course,TOPIC_ZERO_SECTION);
        }
        $html .= html_writer::end_div();
        return $html;
    }
}