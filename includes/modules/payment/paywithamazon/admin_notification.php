<?php
$output = '<div style="padding-top:25px;font-size:16px;text-align:center;">';
if(!$authorized) {
    $output .= '<div style="padding:5px;">Amazon Payments authorization is currently pending.</div>';
    $output .= tep_draw_form('authorize', FILENAME_ORDERS, tep_get_all_get_params(array('action2')) . 'action2=doAuthUpdate', 'post', '', true) . tep_hide_session_id();
    $output .= '<div style="padding:5px;"><input type="submit" name="btndoauthupdate" value="Refresh Authorization Status" /></div>';
    $output .= '</form>';
} else {
    if(!$charged) {
        if(!$voided) {
            $output .= tep_draw_form('capture', FILENAME_ORDERS, tep_get_all_get_params(array('action2')) . 'action2=doCapture', 'post', '', true) . tep_hide_session_id();
            $output .= '<div style="padding:5px;"><input type="submit" name="btndocapture" value="Capture" /></div>';
            $output .= '</form>';
        }
    } else {
        $output .= '<div style="padding:5px;">Funds captured successfully.</div>';
        if(!$refunded) {
            if(!$closed) {
                $output .= tep_draw_form('refund', FILENAME_ORDERS, tep_get_all_get_params(array('action2')) . 'action2=doRefund', 'post', '', true) . tep_hide_session_id();
                $output .= '<div style="padding:5px;"><input type="submit" name="btndorefund" value="Refund" /></div>';
                $output .= '</form>';
            }
        } else {
            if($refund_status) {
                $output .= '<div style="padding:5px;">This order has been refunded.</div>';
            } else {
                $output .= '<div style="padding:5px;">Refund pending (Waiting on Amazon Payments).</div>';
                $output .= tep_draw_form('refund', FILENAME_ORDERS, tep_get_all_get_params(array('action2')) . 'action2=doRefund', 'post', '', true) . tep_hide_session_id();
                $output .= '<div style="padding:5px;"><input type="submit" name="btndorefund" value="Refresh Refund Status" /></div>';
                $output .= '</form>';
            }
        }
    }
    if($charged) {
        if($closed) {
            $output .= '<div style="padding:5px;">This order has been closed.</div>';
        } else {
            $output .= tep_draw_form('void', FILENAME_ORDERS, tep_get_all_get_params(array('action2')) . 'action2=doVoid', 'post', '', true) . tep_hide_session_id();
            $output .= '<input type="hidden" name="close" value="1" />';
            $output .= '<div style="padding:5px;"><input type="submit" name="btndovoid" value="Close" /></div>';
            $output .= '</form>';
        }
    } else {
        if($voided) {
            $output .= '<div style="padding:5px;">This order has been voided.</div>';
        } else {
            $output .= tep_draw_form('void', FILENAME_ORDERS, tep_get_all_get_params(array('action2')) . 'action2=doVoid', 'post', '', true) . tep_hide_session_id();
            $output .= '<div style="padding:5px;"><input type="submit" name="btndovoid" value="Void" /></div>';
            $output .= '</form>';
        }
    }
}