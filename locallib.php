<?php

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

class attforblock_permissions {
    private $canview;
    private $canviewreports;
    private $cantake;
    private $canchange;
    private $canmanage;
    private $canchangepreferences;
    private $canexport;
    private $canbelisted;
    private $canaccessallgroups;

    private $context;

    public function __construct($context) {
        $this->context = $context;
    }

    public function can_view() {
        if (is_null($this->canview))
            $this->canview = has_capability ('mod/attforblock:view', $this->context);

        return $this->canview;
    }

    public function can_viewreports() {
        if (is_null($this->canviewreports))
            $this->canviewreports = has_capability ('mod/attforblock:viewreports', $this->context);

        return $this->canviewreports;
    }

    public function can_take() {
        if (is_null($this->cantake))
            $this->cantake = has_capability ('mod/attforblock:takeattendances', $this->context);

        return $this->cantake;
    }

    public function can_change() {
        if (is_null($this->canchange))
            $this->canchange = has_capability ('mod/attforblock:changeattendances', $this->context);

        return $this->canchange;
    }

    public function can_manage() {
        if (is_null($this->canmanage))
            $this->canmanage = has_capability ('mod/attforblock:manageattendances', $this->context);

        return $this->canmanage;
    }

    public function can_change_preferences() {
        if (is_null($this->canchangepreferences))
            $this->canchangepreferences = has_capability ('mod/attforblock:changepreferences', $this->context);

        return $this->canchangepreferences;
    }

    public function can_export() {
        if (is_null($this->canexport))
            $this->canexport = has_capability ('mod/attforblock:export', $this->context);

        return $this->canexport;
    }

    public function can_be_listed() {
        if (is_null($this->canbelisted))
            $this->canbelisted = has_capability ('mod/attforblock:canbelisted', $this->context);

        return $this->canbelisted;
    }

    public function can_access_all_groups() {
        if (is_null($this->canaccessallgroups))
            $this->canaccessallgroups = has_capability('moodle/site:accessallgroups', $this->context);

        return $this->canaccessallgroups;
    }
}

class attforblock_view_params {
    const VIEW_DAYS             = 1;
    const VIEW_WEEKS            = 2;
    const VIEW_MONTHS           = 3;
    const VIEW_ALLTAKEN         = 4;
    const VIEW_ALL              = 5;

    const SELECTOR_NONE         = 1;
    const SELECTOR_GROUP        = 2;
    const SELECTOR_SESS_TYPE    = 3;

    const SORTED_LIST           = 1;
    const SORTED_GRID           = 2;

    const DEFAULT_VIEW          = self::VIEW_WEEKS;
    const DEFAULT_VIEW_TAKE     = self::SORTED_LIST;
    const DEFAULT_SHOWENDTIME   = 0;

    /** @var int current view mode */
    public $view;

    /** @var int $view and $curdate specify displaed date range */
    public $curdate;

    /** @var int start date of displayed date range */
    public $startdate;

    /** @var int end date of displayed date range */
    public $enddate;

    /** @var int view mode of taking attendance page*/
    public $view_take;

    /** @var int whether sessions end time will be displayed on manage.php */
    public $show_endtime;

    public $students_sort;

    public $student_id;

    private $courseid;

    public function init_defaults($courseid) {
        $this->view = self::DEFAULT_VIEW;
        $this->curdate = time();
        $this->view_take = self::DEFAULT_VIEW_TAKE;
        $this->show_endtime = self::DEFAULT_SHOWENDTIME;

        $this->courseid = $courseid;
    }

    public function init(attforblock_view_params $view_params) {
        $this->init_view($view_params->view);

        $this->init_curdate($view_params->curdate);

        $this->init_view_take($view_params->view_take);

        $this->init_show_endtime($view_params->show_endtime);

        $this->students_sort = $view_params->students_sort;

        $this->student_id = $view_params->student_id;

        $this->init_start_end_date();
    }

    private function init_view($view) {
        global $SESSION;

        if (isset($view)) {
            $SESSION->attcurrentattview[$this->courseid] = $view;
            $this->view = $view;
        }
        elseif (isset($SESSION->attcurrentattview[$this->courseid])) {
            $this->view = $SESSION->attcurrentattview[$this->courseid];
        }
    }

    private function init_curdate($curdate) {
        global $SESSION;

        if ($curdate) {
            $SESSION->attcurrentattdate[$this->courseid] = $curdate;
            $this->curdate = $curdate;
        }
        elseif (isset($SESSION->attcurrentattdate[$this->courseid])) {
            $this->curdate = $SESSION->attcurrentattdate[$this->courseid];
        }
    }

    private function init_view_take($view_take) {
        global $SESSION;

        if (isset($view_take)) {
            set_user_preference("attforblock_view_take", $view_take);
            $this->view_take = $view_take;
        }
        else {
            $this->view_take = get_user_preferences("attforblock_view_take", $this->view_take);
        }
    }

    private function init_show_endtime($show_endtime) {
        global $SESSION;

        if (isset($show_endtime)) {
            set_user_preference("attforblock_showendtime", $show_endtime);
            $this->show_endtime = $show_endtime;
        }
        else {
            $this->show_endtime = get_user_preferences("attforblock_showendtime", $this->show_endtime);
        }
    }

    private function init_start_end_date() {
        $date = usergetdate($this->curdate);
        $mday = $date['mday'];
        $wday = $date['wday'];
        $mon = $date['mon'];
        $year = $date['year'];

        switch ($this->view) {
            case self::VIEW_DAYS:
                $this->startdate = make_timestamp($year, $mon, $mday);
                $this->enddate = make_timestamp($year, $mon, $mday + 1);
                break;
            case self::VIEW_WEEKS:
                $this->startdate = make_timestamp($year, $mon, $mday - $wday + 1);
                $this->enddate = make_timestamp($year, $mon, $mday + 7 - $wday + 1) - 1;
                break;
            case self::VIEW_MONTHS:
                $this->startdate = make_timestamp($year, $mon);
                $this->enddate = make_timestamp($year, $mon + 1);
                break;
            case self::VIEW_ALLTAKEN:
                $this->startdate = 1;
                $this->enddate = time();
                break;
            case self::VIEW_ALL:
                $this->startdate = 0;
                $this->enddate = 0;
                break;
        }
    }
}

class attforblock {
    const SESSION_COMMON        = 0;
    const SESSION_GROUP         = 1;

    const SELECTOR_COMMON       = 0;
    const SELECTOR_ALL          = -1;
    const SELECTOR_NOT_EXISTS   = -2;

    /** @var stdclass course module record */
    public $cm;

    /** @var stdclass course record */
    public $course;

    /** @var stdclass context object */
    public $context;

    /** @var int attendance instance identifier */
    public $id;

    /** @var string attendance activity name */
    public $name;

    /** @var float number (10, 5) unsigned, the maximum grade for attendance */
    public $grade;

    /** @var attforblock_view_params view parameters current attendance instance*/
    public $view_params;

    /** @var attforblock_permissions permission of current user for attendance instance*/
    public $perm;

    private $groupmode;

    private $sessgroupslist;

    private $currentgroup;

    /**
     * Initializes the attendance API instance using the data from DB
     *
     * Makes deep copy of all passed records properties. Replaces integer $course attribute
     * with a full database record (course should not be stored in instances table anyway).
     *
     * @param stdClass $dbrecord Attandance instance data from {attforblock} table
     * @param stdClass $cm       Course module record as returned by {@link get_coursemodule_from_id()}
     * @param stdClass $course   Course record from {course} table
     * @param stdClass $context  The context of the workshop instance
     */
    public function __construct(stdclass $dbrecord, stdclass $cm, stdclass $course, stdclass $context=null) {
        foreach ($dbrecord as $field => $value) {
            if (property_exists('attforblock', $field)) {
                $this->{$field} = $value;
            }
            else {
                throw new coding_exception('The attendance table has field for which there is no property in the attforblock class');
            }
        }
        $this->cm           = $cm;
        $this->course       = $course;
        if (is_null($context)) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = $context;
        }

        $this->view_params = new attforblock_view_params();
        $this->view_params->init_defaults($this->course->id);

        $this->perm = new attforblock_permissions($this->context);
    }

    /**
     * Returns today sessions for this attendance
     *
     * Fetches data from {attendance_sessions}
     *
     * @return array of records or an empty array
     */
    public function get_today_sessions() {
        global $DB;

		$today = time(); // because we compare with database, we don't need to use usertime()
        
        $sql = "SELECT id, groupid, lasttaken
                  FROM {attendance_sessions}
                 WHERE :time BETWEEN sessdate AND (sessdate + duration)
                   AND courseid = :cid AND attendanceid = :aid";
        $params = array(
                'time' => $today,
                'cid' => $this->course->id,
                'aid' => $this->id);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns count of hidden sessions for this attendance
     *
     * Fetches data from {attendance_sessions}
     *
     * @return count of hidden sessions
     */
    public function get_hidden_sessions_count() {
        global $DB;

        $where = "courseid = :cid AND attendanceid = :aid AND sessdate < :csdate";
        $params = array(
                'cid'   => $this->course->id,
                'aid'   => $this->id,
                'csdate'=> $this->course->startdate);

        return $DB->count_records_select('attendance_sessions', $where, $params);
    }

    /**
     * @return moodle_url of manage.php for attendance instance
     */
    public function url_manage() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/attforblock/manage.php', $params);
    }

    /**
     * @return moodle_url of sessions.php for attendance instance
     */
    public function url_sessions() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/attforblock/sessions.php', $params);
    }

    /**
     * @return moodle_url of report.php for attendance instance
     */
    public function url_report() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/attforblock/report.php', $params);
    }

    /**
     * @return moodle_url of export.php for attendance instance
     */
    public function url_export() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/attforblock/export.php', $params);
    }

    /**
     * @return moodle_url of attsettings.php for attendance instance
     */
    public function url_settings() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/attforblock/attsettings.php', $params);
    }

    /**
     * @return moodle_url of attendances.php for attendance instance
     */
    public function url_take() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/attforblock/attendances.php', $params);
    }

    private function calc_groupmode_sessgroupslist_currentgroup(){
        global $USER, $SESSION;

        $cm = $this->cm;
        $this->groupmode = groups_get_activity_groupmode($cm);

        if ($this->groupmode == NOGROUPS)
            return;

        if ($this->groupmode == VISIBLEGROUPS or $this->perm->can_access_all_groups()) {
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // any group in grouping (all if groupings not used)
            // detect changes related to groups and fix active group
            if (!empty($SESSION->activegroup[$cm->course][VISIBLEGROUPS][$cm->groupingid])) {
                if (!array_key_exists($SESSION->activegroup[$cm->course][VISIBLEGROUPS][$cm->groupingid], $allowedgroups)) {
                    // active group does not exist anymore
                    unset($SESSION->activegroup[$cm->course][VISIBLEGROUPS][$cm->groupingid]);
                }
            }
            if (!empty($SESSION->activegroup[$cm->course]['aag'][$cm->groupingid])) {
                if (!array_key_exists($SESSION->activegroup[$cm->course]['aag'][$cm->groupingid], $allowedgroups)) {
                    // active group does not exist anymore
                    unset($SESSION->activegroup[$cm->course]['aag'][$cm->groupingid]);
                }
            }

        } else {
            $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid); // only assigned groups
            // detect changes related to groups and fix active group
            if (isset($SESSION->activegroup[$cm->course][SEPARATEGROUPS][$cm->groupingid])) {
                if ($SESSION->activegroup[$cm->course][SEPARATEGROUPS][$cm->groupingid] == 0) {
                    if ($allowedgroups) {
                        // somebody must have assigned at least one group, we can select it now - yay!
                        unset($SESSION->activegroup[$cm->course][SEPARATEGROUPS][$cm->groupingid]);
                    }
                } else {
                    if (!array_key_exists($SESSION->activegroup[$cm->course][SEPARATEGROUPS][$cm->groupingid], $allowedgroups)) {
                        // active group not allowed or does not exist anymore
                        unset($SESSION->activegroup[$cm->course][SEPARATEGROUPS][$cm->groupingid]);
                    }
                }
            }
        }

        $group = optional_param('group', self::SELECTOR_NOT_EXISTS, PARAM_INT);
        if (!array_key_exists('attsessiontype', $SESSION)) {
            $SESSION->attsessiontype = array();
        }
        if ($group > self::SELECTOR_NOT_EXISTS) {
            $SESSION->attsessiontype[$cm->course] = $group;
        } elseif (!array_key_exists($cm->course, $SESSION->attsessiontype)) {
            $SESSION->attsessiontype[$cm->course] = self::SELECTOR_ALL;
        }

        if ($group == self::SELECTOR_ALL) {
            $this->currentgroup = $group;
            unset($SESSION->activegroup[$cm->course][VISIBLEGROUPS][$cm->groupingid]);
            unset($SESSION->activegroup[$cm->course]['aag'][$cm->groupingid]);
            unset($SESSION->activegroup[$cm->course][SEPARATEGROUPS][$cm->groupingid]);
        } else {
            $this->currentgroup = groups_get_activity_group($cm, true);
            if ($this->currentgroup == 0 and $SESSION->attsessiontype[$cm->course] == self::SELECTOR_ALL) {
                $this->currentgroup = self::SELECTOR_ALL;
            }
        }

        $this->sessgroupslist = array();
        if ($allowedgroups or $this->groupmode == VISIBLEGROUPS or $this->perm->can_access_all_groups()) {
            $this->sessgroupslist[self::SELECTOR_ALL] = get_string('all', 'attforblock');
        }
        if ($this->groupmode == VISIBLEGROUPS) {
            $this->sessgroupslist[self::SELECTOR_COMMON] = get_string('commonsessions', 'attforblock');
        }        
        if ($allowedgroups) {
            foreach ($allowedgroups as $group) {
                $this->sessgroupslist[$group->id] = format_string($group->name);
            }
        }
    }

    public function get_group_mode() {
        if (is_null($this->groupmode))
            $this->calc_groupmode_sessgroupslist_currentgroup();

        return $this->groupmode;
    }

    public function get_sess_groups_list() {
        if (is_null($this->sessgroupslist))
            $this->calc_groupmode_sessgroupslist_currentgroup();

        return $this->sessgroupslist;
    }

    public function get_current_group() {
        if (is_null($this->currentgroup))
            $this->calc_groupmode_sessgroupslist_currentgroup();

        return $this->currentgroup;
    }
}


?>
