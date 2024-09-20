<!-- HIDDEN PREHEADER TEXT -->
<div
    style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: 'Lato', Helvetica, Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
    {{ $data['preheader'] ?? '' }}
</div>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <!-- LOGO -->
    <tr>
        <td bgcolor="{{ $data['theme']['header_bg_color'] }}" align="center"
            style="background-color: {{ $data['theme']['header_bg_color'] }}">
            <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="{{ $data['configs']['content_width'] }}">
                    <tr>
                        <td align="center" valign="top" width="{{ $data['configs']['content_width'] }}">
                <![endif]-->
            <table border="0" cellpadding="0" cellspacing="0" width="100%"
                style="max-width: {{ $data['configs']['content_width'] }}px;">
                <tr>
                    <td align="center" valign="top" style="padding: 30px 10px 30px 10px;">
                        <a href="{{ \Illuminate\Support\Facades\URL::to('/') }}" target="_blank"
                            title="{{ $data['configs']['company_name'] }}">
                            <img alt="{{ $data['configs']['company_name'] }} Logo" src="{{ $data['logo'] }}"
                                width="{{ $data['configs']['logo_width'] }}"
                                height="{{ $data['configs']['logo_widht'] }}"
                                style="display: block; width: {{ $data['configs']['logo_width'] }}px; max-width: {{ $data['configs']['logo_width'] }}px; 
                                     min-width: {{ $data['configs']['logo_width'] }}px;"
                                border="0">
                        </a>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
        </td>
    </tr>
