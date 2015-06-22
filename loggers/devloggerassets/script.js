$(document).ready(function () {
    var DevLogger_Assist = new MPF_DevLogger_Assist();
    var DevLogger_Debug = new MPF_DevLogger_Debug();
    var DevLogger_Error = new MPF_DevLogger_Error();
    $('<div>').attr('id', 'devlogger-main').appendTo($('body')); //.addClass('alwaysvisible');
    $('<div>').attr('id', 'devlogger-title-bar').appendTo($('#devlogger-main'))
        .html('<b>Developer Info (' + DevLogger_RunTime + 's)</b><b id="devlogger-title-errors"></b><span><a onclick="return MPF_DevLogger_Filter(this, \'all\');" class="selected" href="#">All</a><a onclick="return MPF_DevLogger_Filter(this, \'error;alert;critical;warning;notice;emergency\');" href="#">Error</a><a onclick="return MPF_DevLogger_Filter(this, \'debug\');" href="#">Debug</a><a onclick="return MPF_DevLogger_Filter(this, \'info\');" href="#">Info</a><a onclick="return MPF_DevLogger_Filter(this, \'debug-query\');" href="#">Query</a></span>');
    $('<div>').attr('id', 'devlogger-logs').appendTo($('#devlogger-main'));
    $('<ul>').attr('id', 'devlogger-list').appendTo($('#devlogger-logs'));
    var errorsNumber = 0;
    $.each(DevLogger_Logs, function (index, value) {
        if (value.level == 'emergency') {
            errorsNumber++;
            DevLogger_Assist.LogLevelEmergency(value);
        } else if (value.level == 'alert') {
            errorsNumber++;
            DevLogger_Assist.LogLevelAlert(value);
        } else if (value.level == 'critical') {
            errorsNumber++;
            DevLogger_Assist.LogLevelCritical(value);
        } else if (value.level == 'error') {
            errorsNumber++;
            DevLogger_Assist.LogLevelError(value, DevLogger_Error);
        } else if (value.level == 'warning') {
            errorsNumber++;
            DevLogger_Assist.LogLevelWarning(value, DevLogger_Error);
        } else if (value.level == 'notice') {
            errorsNumber++;
            DevLogger_Assist.LogLevelNotice(value, DevLogger_Error);
        } else if (value.level == 'info') {
            DevLogger_Assist.LogLevelInfo(value);
        } else if (value.level == 'debug') {
            DevLogger_Assist.LogLevelDebug(value, DevLogger_Debug);
        } else {
            var preText = '';
            if (value.context.File) {
                preText = value.context.File + ' [' + value.context.Line + ']: ';
            }
            $('<li>').appendTo($('#devlogger-list')).html(preText + value.message).addClass('mpf-dev-' + value.level);
        }
    });
    if (errorsNumber) {
        $('#devlogger-title-errors').text('( ' + errorsNumber + ' errors )');
    }
});


function MPF_DevLogger_Assist() {
}

MPF_DevLogger_Assist.prototype = {
    ShowClass: function (name) {
        return "<span class='class-name'>" + name + "</span>";
    },
    LogLevelEmergency: function (value) {
        var preText = '';
        if (value.context.File) {
            preText = value.context.File + ' [' + value.context.Line + ']: ';
        }
        $('<li>').appendTo($('#devlogger-list')).html(this.ShowClass(value.context.fromClass + " " + value.context.logTime) + preText + value.message).addClass('mpf-dev-' + value.level);
    },
    LogLevelAlert: function (value) {
        var preText = '';
        if (value.context.File) {
            preText = value.context.File + ' [' + value.context.Line + ']: ';
        }
        $('<li>').appendTo($('#devlogger-list')).html(this.ShowClass(value.context.fromClass + " " + value.context.logTime) + preText + value.message).addClass('mpf-dev-' + value.level);
    },
    LogLevelCritical: function (value) {
        var preText = '';
        if (value.context.File) {
            preText = value.context.File + ' [' + value.context.Line + ']: ';
        }
        $('<li>').appendTo($('#devlogger-list')).html(this.ShowClass(value.context.fromClass + " " + value.context.logTime) + preText + value.message).addClass('mpf-dev-' + value.level);
    },
    LogLevelError: function (value, DevLogger_Error) {
        DevLogger_Error.Display(value);
    },
    LogLevelWarning: function (value, DevLogger_Error) {
        DevLogger_Error.Display(value);
    },
    LogLevelNotice: function (value, DevLogger_Error) {
        DevLogger_Error.Display(value);
    },
    LogLevelInfo: function (value) {
        var preText = '';
        if (value.context.File) {
            preText = value.context.File + ' [' + value.context.Line + ']: ';
        }
        $('<li>').appendTo($('#devlogger-list')).html(this.ShowClass(value.context.fromClass + " " + value.context.logTime) + preText + value.message).addClass('mpf-dev-' + value.level);
    },
    /**
     *
     * @param value
     * @param MPF_DevLogger_Debug DebugAssist
     * @constructor
     */
    LogLevelDebug: function (value, DebugAssist) {
        if ('mpf\\datasources\\sql\\PDOConnection' == value.context.fromClass) {
            // special display for queries
            DebugAssist.PDOConnection(value);
        } else {
            $('<li>').appendTo($('#devlogger-list')).html(this.ShowClass(value.context.fromClass + " " + value.context.logTime) + value.message).addClass('mpf-dev-debug');
        }
    }
}

function MPF_DevLogger_Filter(element, what) {
    $('a', element.parentNode).removeClass('selected');
    $(element).addClass('selected');
    if ('all' == what) {
        $('li', $('#devlogger-list')).show();
    } else {
        $('li', $('#devlogger-list')).hide();
        var allWhat = what.split(";");
        for (var i = 0; i < allWhat.length; i++) {
            $('li.mpf-dev-' + allWhat[i], $('#devlogger-list')).show();
        }
    }
}

function MPF_DevLogger_Debug() {
}

MPF_DevLogger_Debug.prototype = {
    ShowClass: function (name, time) {
        return "<span class='class-name'>" + name + " " + time + "</span>";
    },
    PDOConnection: function (value) {
        var context = value.context;

        var $li = $('<li>')
            .appendTo($('#devlogger-list'))
            .html(this.ShowClass(value.context.fromClass, value.context.logTime)) // + value.message
            .addClass('mpf-dev-debug-query')
            .click(function () {
                if ($(this).hasClass('devlogger-querylog-extended')) {
                    $(this).removeClass('devlogger-querylog-extended');
                } else {
                    $(this).addClass('devlogger-querylog-extended');
                }
            });

        if (context.intoArray) {
            $('<span>').html('Assoc: ').appendTo($li).addClass('mpf-dev-message-into');
        } else if (context.intoClass) {
            $('<span>').html(context.intoClass + ': ').appendTo($li).addClass('mpf-dev-message-into');
        } else if (context.intoObject) {
            $('<span>').html('Object: ').appendTo($li).addClass('mpf-dev-message-into');
        } else if (context.execute) {
            $('<span>').html('Execute: ').appendTo($li).addClass('mpf-dev-message-into');
        }
        $('<span>').html(value.message).appendTo($li).addClass('mpf-dev-message-query');
        $('<div>').html(this._PDO_QueryDetailsText(context)).appendTo($li).addClass('mpf-dev-message-details');
    },
    _PDO_QueryDetailsText: function (context) {
        if (context.params) {
            var response = "<b>Params:</b><br />";
            $.each(context.params, function (index, value) {
                response += "&nbsp;&nbsp;&nbsp;&nbsp;<i>" + index + "</i>&nbsp;&nbsp;&nbsp;<span>" + value + "</span> <br />"
            });
        } else {
            var response = "";
        }

        response += "<b>Query Time:</b><br /><span>" + context.time + "</span><br />";
        response += "<b>Rows:</b><br /><span>" + context.rows + "</span><br />";
        response += "<b>Database:</b><br /><span>" + context.dns + "</span><br />"
        return response;
    }
}

function MPF_DevLogger_Error() {

}

MPF_DevLogger_Error.prototype = {
    ShowClass: function (name, time) {
        return "<span class='class-name'>" + name + " " + time + "</span>";
    },
    Display: function (value) {
        var $li = $('<li>')
            .appendTo($('#devlogger-list'))
            .html(this.ShowClass(value.context.fromClass, value.context.logTime)) // + value.message
            .addClass('mpf-dev-' + value.level)
            .click(function () {
                if ($(this).hasClass('devlogger-error-extended')) {
                    $(this).removeClass('devlogger-error-extended');
                } else {
                    $(this).addClass('devlogger-error-extended');
                }
            });
        $('<span>').html(value.message).appendTo($li).addClass('mpf-dev-message-error');
        if (value.context.exception) {
            $('<div>').html(this._ExceptionDetails(value.context.exception)).appendTo($li).addClass('mpf-dev-message-details');
        } else {
            $('<div>').html(this._ErrorDetails(value.context)).appendTo($li).addClass('mpf-dev-message-details');
        }
    },
    _ExceptionDetails: function (exception) {
        var response = "<b>" + exception.exceptionClass + "</b><br />";
        response += "<b>File:</b><span>" + exception.file + "</span><br />";
        response += "<b>Line:</b><span>" + exception.line + "</span><br />";
        response += "<b>Code:</b><span>" + exception.code + "</span><br />";
        response += "<b>Trace:</b><br /><span><pre>" + exception.stringTrace + "</pre></span><br />";
        return response;
    },
    _ErrorDetails: function (context) {
        console.log(context);
        if (!context.File) {
            return " -- needs to be parsed --";
        }
        var response = "<b>File:</b><span>" + context.File + "</span><br />";
        response += "<b>Line:</b><span>" + context.Line + "</span><br />";
        if (context.Query) {
            response += "<b>Query:</b><span>" + context.Query + "</span><br />";
        }
        if (context.Params) {
            response += "<b>Params:</b><br />";
            $.each(context.Params, function (index, value) {
                response += "&nbsp;&nbsp;&nbsp;&nbsp;<i>" + index + "</i>&nbsp;&nbsp;&nbsp;<span>'" + value + "'</span> <br />"
            });
        }
        response += "<b>Trace:</b><br /><span><pre>" + context.Trace + "</pre></span><br />";
        return response;
    }
}