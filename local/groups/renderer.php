<?php
/**
 * The renderer for the groups module.
 *
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_groups_renderer extends plugin_renderer_base  {
    
    protected function render_local_groups(local_groups $renderable) {
        return $this->display($renderable->context, $renderable->groups,$renderable->showall,$renderable->searchquery,$renderable->page);
     }
    public function display($context, $groups, $showall, $searchquery, $page) {
        global $DB, $OUTPUT, $PAGE, $CFG, $USER;
        $output = '';
        $data = array();
        $editcolumnisempty = true;
        $params = array('page' => $page);
        if ($context->id) {
            $params['contextid'] = $context->id;
        }
        if ($searchquery) {
            $params['search'] = $searchquery;
        }
        if ($showall) {
            $params['showall'] = true;
        }
        $baseurl = new moodle_url('/local/groups/index.php', $params);
        foreach($groups['groups'] as $cohort) {
            $line = array();
            $urlparams = array('id' => $cohort->id, 'returnurl' => $baseurl->out_as_local_url());
            $cohortcontext = context::instance_by_id($cohort->contextid);
            $cohort->description = file_rewrite_pluginfile_urls($cohort->description, 'pluginfile.php', $cohortcontext->id,
                    'cohort', 'description', $cohort->id);
        
            $buttons = array();
            if (empty($cohort->component)) {
                $cohortmanager = has_capability('moodle/cohort:manage', $cohortcontext);
                $cohortcanassign = has_capability('moodle/cohort:assign', $cohortcontext);
        
                
                $showhideurl = new moodle_url('/local/groups/edit.php', $urlparams + array('sesskey' => sesskey()));
                if ($cohortmanager) {
                    $buttons[] = html_writer::link(new moodle_url('/local/groups/edit.php', $urlparams),
                        $OUTPUT->pix_icon('t/edit', get_string('edit')),
                        array('title' => get_string('edit')));
                    $editcolumnisempty = false;
                    if ($cohort->visible) {
                        $showhideurl->param('hide', 1);
                        $visibleimg = $OUTPUT->pix_icon('t/hide', get_string('inactive'));
                        $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('inactive')));
                    } else {
                        $showhideurl->param('show', 1);
                        $visibleimg = $OUTPUT->pix_icon('t/show', get_string('active'));
                        $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('active')));
                    }                    
                }
                if ($cohortcanassign) {
                    $buttons[] = html_writer::link(new moodle_url('/local/groups/assign.php', $urlparams),
                        $OUTPUT->pix_icon('i/enrolusers', get_string('assign', 'core_cohort')),
                        array('title' => get_string('assign', 'core_cohort')));
                    $editcolumnisempty = false;
                    $buttons[] = html_writer::link(new moodle_url('/local/groups/mass_enroll.php', $urlparams),
                        $OUTPUT->pix_icon('i/users', get_string('bulk_enroll', 'local_groups')),
                        array('title' => get_string('bulk_enroll', 'local_groups')));
                }
                if ($cohortmanager)
                $buttons[] = html_writer::link(
                                "javascript:void(0)",
                                $OUTPUT->pix_icon('i/delete', get_string('delete'), 'moodle', array('title' => '')),
                                array('id' => 'deleteconfirm' . $cohort->id . '', 'onclick' => '(
                                      function(e){
                        require("local_groups/renderselections").deletecohort(' . $cohort->id . ', "' . $cohort->name . '")
                        })(event)'));
            }
            $line[] = implode(' ', $buttons);
            if ($showall) {
                if ($cohortcontext->contextlevel == CONTEXT_COURSECAT) {
                    $line[] = html_writer::link(new moodle_url('/cohort/index.php' ,
                            array('contextid' => $cohort->contextid)), $cohortcontext->get_context_name(false));
                } else {
                    $line[] = $cohortcontext->get_context_name(false);
                }
            }
            $tmpl = new local_groups\output\cohortname($cohort);
            $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
            $tmpl = new local_groups\output\cohortidnumber($cohort);
            $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
            // $line[] = format_text($cohort->description, $cohort->descriptionformat);
            $description = format_text($cohort->description, $cohort->descriptionformat);
            $descriptionstring = strlen($description) > 50 ? substr($description, 0, 50)."..." : $description;
            $descriptiontitle = $description;
            $line[] = '<span title="'.$descriptiontitle.'">'.$descriptionstring.'</span>';
            $group_members_count = $DB->count_records('cohort_members', array('cohortid'=>$cohort->id));
            $line[] = html_writer::link(new moodle_url('/local/groups/assign.php', $urlparams),
                        $group_members_count,
                        array('title' => get_string('assign', 'core_cohort')));
                    $editcolumnisempty = false;
        
            if (empty($cohort->component)) {
                $line[] = get_string('nocomponent', 'cohort');
            } else {
                $line[] = get_string('pluginname', $cohort->component);
            }
        
            $data[] = $row = new html_table_row($line);
            if (!$cohort->visible) {
                $row->attributes['class'] = 'dimmed_text';
            }
        }
        $table = new html_table();

        if (!$editcolumnisempty) {
            $editcell[] = get_string('edit');
            $table->colclasses[] = 'centeralign action';
        } else {
        // Remove last column from $data.
            foreach ($data as $row) {
            array_pop($row->cells);
            }
        }
        if ($editcell) {
            $table->head  = array_merge($editcell, array(get_string('name', 'local_groups'), get_string('idnumber', 'local_groups'), get_string('costcenter', 'local_groups'),get_string('department', 'local_groups'),get_string('memberscount', 'local_groups'),  get_string('component', 'local_groups')));
        } else {
          $table->head  = array(get_string('name', 'local_groups'), get_string('idnumber', 'local_groups'), get_string('costcenter', 'local_groups'),get_string('department', 'local_groups'), get_string('memberscount', 'local_groups'),  get_string('component', 'local_groups')); 
        }
        
        // print_object($table->head);exit;
        // $table->size = array('16.5%', '16.5%', '16.5%', '16.5%', '16.5%', '16.5%');
        // $table->align = array('left', 'left', 'left', 'left', 'left', 'left');
        // $table->width = '99%';
        $table->colclasses = array('leftalign name', 'leftalign id', 'leftalign description', 'leftalign size','centeralign source');
        if ($showall) {
            array_unshift($table->head, get_string('category'));
            array_unshift($table->colclasses, 'leftalign category');
        }
        $table->id = 'cohorts';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = $data;
        $output .= html_writer::table($table);
        if(!empty($data)){
            $result = $output;
        } else {
            $result = '<div class="alert alert-info text-center">No Groups to Show</div>';
        }
        return $result;
    }
    function groups_view($filterdata,$page,$perpage){
        global $PAGE;
        $filterjson = json_encode($filterdata);

        $PAGE->requires->js_call_amd('local_groups/groupviews', 'groupsDatatable', array('filterdata'=> $filterjson));

        $table = new html_table();
        $table->id = "viewgroups";

         $table->head  = array(get_string('name', 'local_groups'), get_string('idnumber', 'local_groups'), get_string('costcenter', 'local_groups'),get_string('department', 'local_groups'),   get_string('memberscount', 'local_groups'), /* get_string('component', 'local_groups'),*/get_string('actions', 'local_groups')); 

        $table->align = array('center','left', 'center', 'left','left');
        $table->size = array('20%','20%','20%','20%','20%');


        $output = '<div class="w-full pull-left">'. html_writer::table($table).'</div>';
        return $output;
    }
}

