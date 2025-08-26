// File path: amd/src/feedbackmodule.js
define(['jquery', 'core/ajax', 'local_edwiserreports/select2'], function ($, Ajax) {

    const SELECTOR = {
        PANEL: '#feedbackmod',
        TABLEAREA: '.tablearea',
        COURSE: '.course-select',
        MODULE: '.feedback-select',
        COHORT: '.user-select',
        MODULENAME: '#feedbackname'
    };

    let filter = {
        course: 0,
        cohort: 0,
        dir: $('html').attr('dir'),
        profilefield: ''
    };

    /**
     * Get course feedback chart data.
     */
    function getCourseFeedback() {
        return Ajax.call([{
            methodname: 'local_dashboardv2_get_feedbacks',
            args: {
                profilefield: filter.profilefield,
                courseid: filter.course,
                username: filter.cohort,
                moduleid: filter.group ?? 0,
            }
        }])[0];
    }

    /**
     *  Get table name.
     */
    function fn_get_table_names() {
        $(SELECTOR.MODULE).empty();
        getCourseFeedback().then((response) => {
            response.feedbacks.forEach(function (cl) {
                $(SELECTOR.MODULE).append($("<option></option>")
                    .attr("value", cl.id)
                    .text(cl.name + '(' + cl.section + ')')
                    .attr("title", cl.section)
                    .attr("data-cmid", cl.cmid)
                );
            });
            if (filter.group !== undefined && filter.group !== 0) {
                $(`${SELECTOR.PANEL} ${SELECTOR.MODULE}`).val(filter.group);
            } else {
                $(`${SELECTOR.PANEL} ${SELECTOR.MODULE}`).val(response.feedbacks[0].id);
            }

            filter.group = $(`${SELECTOR.PANEL} ${SELECTOR.MODULE}`).val();

            if (response.report.length === 0) {
                $(SELECTOR.TABLEAREA).html('<div class="alert alert-dark m-5">The feedback has not been published yet.</div>');
                $(".fp-btn").attr("href", "").css({ 'pointer-events': 'none', 'opacity': 0.3 });
                $(`${SELECTOR.PANEL} ${SELECTOR.MODULENAME}`).html("");
                return;
            }

            loadTableData(response.report, response.totalusers);
        });
    }

    /**
     * load table data
     *
     * @param {any} response
     * @param {BigInt} totalUsers
     *
     * @returns
     */
    function loadTableData(response, totalUsers = 0) {
        if (typeof filter.group === 'undefined') {
            return;
        }

        let entry_id = $(`${SELECTOR.PANEL} ${SELECTOR.MODULE}`).find('option:selected')[0].dataset.cmid;
        let para = '?id=' + entry_id;
        $(".fp-btn").attr("href", M.cfg.wwwroot + "/mod/feedback/show_entries.php" + para);
        $(".fp-btn").css({ 'pointer-events': 'auto', 'opacity': 1 });

        $(SELECTOR.PANEL).find(SELECTOR.FORMFILTER).val(JSON.stringify(filter));

        $(SELECTOR.TABLEAREA).html('');
        generateTable(response);
        $(`${SELECTOR.PANEL} ${SELECTOR.MODULENAME}`).html(
            "Total User: <b>" + totalUsers + "</b>"
        );
    }

    /**
     * Generate Table
     * @param {any} data
     */
    function generateTable(data) {
        let sForm = '<table class="table table-striped table-hover">';
        sForm += '<thead class="table-light">';
        sForm += '<tr>';
        sForm += '<th>Reason</th><th>Excellent</th><th>Good</th><th>Average</th>';
        sForm += '<th>Needs Improvement</th><th>Average Rating</th></tr>';
        sForm += '</thead><tbody>';

        data.forEach(function (cl) {
            let spanClass = '';
            if (cl.id !== '9999') {
                if (cl.final_category === 'Excellent') {
                    spanClass = 'badge bg-success text-light';
                } else if (cl.final_category === 'Good') {
                    spanClass = 'badge bg-info text-light';
                } else if (cl.final_category === 'Average') {
                    spanClass = 'badge bg-primary text-light';
                } else if (cl.final_category === 'Needs Improvement') {
                    spanClass = 'badge bg-danger text-light';
                }
            }
            const spanWrapper = '<span class="' + spanClass + '">' + cl.final_category + '</span>';
            sForm += '<tr><td>' + cl.question + '</td>';
            sForm += '<td class="text-center">' + cl.excellent + '</td>';
            sForm += '<td class="text-center">' + cl.good + '</td>';
            sForm += '<td class="text-center">' + cl.average + '</td>';
            sForm += '<td class="text-center">' + cl.needs_improvement + '</td>';
            sForm += '<td class="text-center">' + spanWrapper +
                ' <span class="d-block" style="font-size:12px;">(' + cl.avg_score + ')</span></td></tr>';
        });

        sForm += '</tbody></table>';
        $(SELECTOR.TABLEAREA).html(sForm);
    }

    /**
     * Public init method
     */
    function init() {

        if ($(SELECTOR.COURSE).length === 0) {
            return;
        }


        $(SELECTOR.PANEL + ' .singleselect').select2();

        $('body').on('change', `${SELECTOR.PANEL} ${SELECTOR.COHORT}`, function () {
            filter.course = $(`${SELECTOR.PANEL} ${SELECTOR.COURSE}`).val();
            filter.group = $(`${SELECTOR.PANEL} ${SELECTOR.MODULE}`).val();
            filter.profilefield = $(this).attr('data-for');
            filter.cohort = $(this).val();
            fn_get_table_names();
        });

        $('body').on('change', `${SELECTOR.PANEL} ${SELECTOR.COURSE}`, function () {
            filter.course = parseInt($(this).val());
            filter.cohort = $(`${SELECTOR.PANEL} ${SELECTOR.COHORT}`).val();
            filter.profilefield = $(`${SELECTOR.PANEL} ${SELECTOR.COHORT}`).attr('data-for');
            filter.group = 0;
            fn_get_table_names();
        });

        $('body').on('change', `${SELECTOR.PANEL} ${SELECTOR.MODULE}`, function () {
            filter.course = $(`${SELECTOR.PANEL} ${SELECTOR.COURSE}`).val();
            filter.cohort = $(`${SELECTOR.PANEL} ${SELECTOR.COHORT}`).val();
            filter.profilefield = $(`${SELECTOR.PANEL} ${SELECTOR.COHORT}`).attr('data-for');

            filter.group = parseInt($(this).val());
            fn_get_table_names();
        });

        filter.course = $(`${SELECTOR.PANEL} ${SELECTOR.COURSE}`).val();
        filter.cohort = $(`${SELECTOR.PANEL} ${SELECTOR.COHORT}`).val();
        filter.profilefield = $(`${SELECTOR.PANEL} ${SELECTOR.COHORT}`).attr('data-for');

        fn_get_table_names();
    }

    return {
        init: init
    };
});
