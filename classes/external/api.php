<?php

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class local_dashboardv2_api extends external_api
{

    /**
     * Returns description of method parameters
     */
    public static function get_feedbacks_parameters()
    {
        return new external_function_parameters(
            array(
                'profilefield' => new external_value(PARAM_TEXT, 'Feedback report for area manager or nutrition officer'),
                'username' => new external_value(PARAM_USERNAME, 'username of area manager or nutrition officer'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module_id', VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Returns the feedbacks activities and report of feedback.
     */
    public static function get_feedbacks(string $profilefield, string $username, int $courseid, int $moduleid = 0)
    {
        global $DB;
        self::validate_parameters(
            self::get_feedbacks_parameters(),
            ['profilefield' => $profilefield, 'username' => $username, 'courseid' => $courseid, 'moduleid' => $moduleid]
        );

        $feedbacks = self::get_course_feedback($courseid);

        $activityid = $moduleid !== 0 ? $moduleid : reset($feedbacks)->id;

        $sql = "WITH feedback_values AS (
                    SELECT
                        fi.id AS questionid,
                        fi.name AS question,
                         CASE
                            WHEN fv.value = '4' THEN 4
                            WHEN fv.value = '3' THEN 3
                            WHEN fv.value = '2' THEN 2
                            WHEN fv.value = '1' THEN 1
                        END AS score,
                        uk.id AS userid,
                        ud.data AS region
                    FROM {feedback} f
                    JOIN {feedback_item}       fi ON fi.feedback  = f.id
                    JOIN {feedback_value}      fv ON fv.item      = fi.id
                    JOIN {feedback_completed}  fc ON fc.id        = fv.completed
                    JOIN {user}                uk ON uk.id        = fc.userid
                    LEFT JOIN {user_info_data} ud ON ud.userid    = uk.id
                    JOIN {user_info_field}     uf ON uf.id        = ud.fieldid 
                    WHERE f.id = :fmyid
                    AND ud.data = :username
                    AND uf.shortname = :fieldshortname
                ),
                aggregated AS (
                    SELECT
                        questionid,
                        question,
                        SUM(CASE WHEN score = 4 THEN 1 ELSE 0 END) AS excellent,
                        SUM(CASE WHEN score = 3 THEN 1 ELSE 0 END) AS good,
                        SUM(CASE WHEN score = 2 THEN 1 ELSE 0 END) AS average,
                        SUM(CASE WHEN score = 1 THEN 1 ELSE 0 END) AS needs_improvement,
                        AVG(score) AS avg_score
                    FROM feedback_values
                    GROUP BY questionid, question
                )
                SELECT
                    questionid AS id,
                    question,
                    excellent,
                    good,
                    average,
                    needs_improvement,
                    ROUND(avg_score, 2) as avg_score,
                    CASE
                        WHEN avg_score >= 3.5 THEN 'Excellent'
                        WHEN avg_score >= 2.5 THEN 'Good'
                        WHEN avg_score >= 1.5 THEN 'Average'
                        ELSE 'Needs Improvement'
                    END AS final_category
                FROM aggregated
                ORDER BY questionid;
                ";

        $params = [
            'fmyid' => $activityid,
            'username' => $username,
            'fieldshortname' => $profilefield
        ];

        $report = $DB->get_records_sql($sql, $params);
        $sqlusers = "SELECT count(*) as total
                    FROM {feedback_completed} fc 
                    JOIN {user} u ON fc.userid = u.id
                    JOIN {user_info_data} ud ON u.id = ud.userid
                    JOIN {user_info_field} uf ON uf.id = ud.fieldid 
                    WHERE fc.feedback = :fmyid AND ud.data = :username AND uf.shortname = :fieldshortname";
        $totalUsers = $DB->get_record_sql($sqlusers, $params);
        return [
            'feedbacks' => $feedbacks,
            'report' => $report !== false ? $report : [],
            'totalusers' => $totalUsers->total,
        ];
    }

    public static function get_feedbacks_returns()
    {
        return new external_single_structure([
            'report' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Question id'),
                    'question'    => new external_value(PARAM_TEXT, 'question string of feedback'),
                    'excellent'  => new external_value(PARAM_INT, 'count of excellent feedback responses'),
                    'good'  => new external_value(PARAM_INT, 'count of good feedback responses'),
                    'average'  => new external_value(PARAM_INT, 'count of average feedback responses'),
                    'needs_improvement'  => new external_value(PARAM_INT, 'count of improvement feedback responses'),
                    'avg_score'  => new external_value(PARAM_RAW, 'avg score'),
                    'final_category'  => new external_value(PARAM_RAW, 'Final Category'),
                ])
            ),
            'feedbacks' => new external_multiple_structure(
                new external_single_structure([
                    'id'    => new external_value(PARAM_INT, 'activityid'),
                    'name'  => new external_value(PARAM_RAW, 'feedback name'),
                    'section'  => new external_value(PARAM_RAW, 'section name of feedback'),
                    'cmid'  => new external_value(PARAM_INT, 'Course module id'),
                ])
            ),
            'totalusers' => new external_value(PARAM_INT, 'Total users')
        ]);
    }


    private static function get_course_feedback(int $courseid)
    {
        global $DB;
        $sql = "SELECT DISTINCT
                f.id,
                f.name,
                COALESCE(parent_section.name, cs.name) AS section,
                cm.id AS cmid
            FROM
                {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course} c ON c.id = cm.course
                JOIN {feedback} f ON f.id = cm.instance
                LEFT JOIN {course_sections} cs ON cs.id = cm.section
                LEFT JOIN (
                    SELECT
                        cfo.sectionid,
                        cs.name,
                        cs.id
                    FROM {course_sections} cs
                    LEFT JOIN {course_format_options} cfo ON cfo.value = cs.section
                    WHERE
                        cfo.name = 'parent'
                ) AS parent_section ON parent_section.sectionid = cs.id AND parent_section.id = c.id
            WHERE
                c.visible = 1
                AND c.id = :courseid
            AND m.name = 'feedback'";

        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }
}
