<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of the Roundcube Webmail client                     |
 |                                                                       |
 | Copyright (C) The Roundcube Dev Team                                  |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Return message meta data about headers and content                  |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <roundcube@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

class rcmail_action_mail_getmsgdata extends rcmail_action_mail_index
{
    protected static $mode = self::MODE_AJAX;

    protected static $MESSAGE;

    public function run($args = [])
    {
        $rcmail = rcmail::get_instance();
        $msg_id = rcube_utils::get_input_string('_uid', rcube_utils::INPUT_GET);
        $uid = preg_replace('/\.[0-9.]+$/', '', $msg_id);
        $mbox_name = $rcmail->storage->get_folder();

        $rcmail->config->set('prefer_html', true);

        if (empty($uid)) {
            $rcmail->output->show_message('nouid', 'error');
        }

        if (isset($_GET['_safe'])) {
            $is_safe = (bool) $_GET['_safe'];
        } else {
            $is_safe = null;
        }
        $MESSAGE = new rcube_message($msg_id, $mbox_name, $is_safe);

        self::$MESSAGE = $MESSAGE;

        // if message not found (wrong UID)...
        if (empty($MESSAGE->headers)) {
            self::display_server_error();
        }

        # TODO: necessary?
        // set message charset as default
        if (!empty($MESSAGE->headers->charset)) {
            $rcmail->storage->set_charset($MESSAGE->headers->charset);
        }

        // TODO: suspicious_content_warning
        // TODO: SUSPICIOUS_EMAIL warning
        // TODO: inject CSP that restricts everything: "<meta http-equiv='Content-Security-Policy' content=\"default-src 'none'; img-src 'none'; child-src 'none';\" />";



        $mime_parts_data = [];
        $replacements = [];

        foreach ($MESSAGE->parts as $part) {
            foreach ($part->replaces as $key => $value) {
                $replacements[] = preg_replace('/^cid:/', '', $key);
            }
            $data = [
                'mimetype' => $part->mimetype,
                'mime_id' => $part->mime_id,
            ];
            if ($part->mimetype === 'text/html') {
                $body = self::$MESSAGE->get_part_body($part->mime_id, true);

                // TODO: cache the "washed" result somewhere so it can be reused when it's being fetched?

                // Unset `inline_html` to make the washer not strip but check
                // the `head`-element, too.
                self::wash_html($body, ['inline_html' => false], $part->replaces);
                $data['has_remote_objects'] = self::$REMOTE_OBJECTS;
            }
            $mime_parts_data[] = $data;
        }

        // rcube::write_log("DBG", ['attachments' => $MESSAGE->attachments]);
        // rcube::write_log("DBG", ['inline_parts' => $MESSAGE->inline_parts]);
        // rcube::write_log("DBG", ['replacements' => $replacements]);

        $attachments = array_filter($MESSAGE->attachments, fn($part) => $MESSAGE->is_standalone_attachment($part));

        $msgdata = [
            'uid' => $uid,
            'load_remote_objects' => $MESSAGE->is_safe,
            'headers' => [
                'subject' => $MESSAGE->subject,
                'from' => $MESSAGE->sender,
                'to' => $MESSAGE->headers->to,
                'date' => $MESSAGE->headers->date,
            ],
            'parts_data' => $mime_parts_data,
            'attachments' => $attachments
        ];

        $rcmail->output->add_data($msgdata);

        // send response
        $rcmail->output->send();
    }

}
