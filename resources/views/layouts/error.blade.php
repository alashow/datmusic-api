<!DOCTYPE html>
<html>
    <head>
        <title>@yield('title')</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                background: #c0392b;
                color: #fff;
                display: table;
                font-weight: 700;
                font-family: 'Ubuntu', sans-serif;
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 72px;
                margin-bottom: 40px;
                text-shadow:rgb(173,51,39) 1px 1px,rgb(173,51,39) 2px 2px,rgb(173,51,39) 3px 3px,rgb(173,51,39) 4px 4px,rgb(173,51,39) 5px 5px,rgb(173,51,39) 6px 6px,rgb(173,51,39) 7px 7px,rgb(173,51,39) 8px 8px,rgb(173,51,39) 9px 9px,rgb(173,51,39) 10px 10px,rgb(173,51,39) 11px 11px,rgb(173,51,39) 12px 12px,rgb(173,51,39) 13px 13px,rgb(173,51,39) 14px 14px,rgb(173,51,39) 15px 15px,rgb(173,51,39) 16px 16px,rgb(173,51,39) 17px 17px,rgb(173,51,39) 18px 18px,rgb(173,51,39) 19px 19px,rgb(173,51,39) 20px 20px,rgb(173,51,39) 21px 21px,rgb(174,51,39) 22px 22px,rgb(174,51,39) 23px 23px,rgb(175,51,39) 24px 24px,rgb(175,51,39) 25px 25px,rgb(176,52,39) 26px 26px,rgb(177,52,39) 27px 27px,rgb(177,52,40) 28px 28px,rgb(178,52,40) 29px 29px,rgb(178,52,40) 30px 30px,rgb(179,53,40) 31px 31px,rgb(180,53,40) 32px 32px,rgb(180,53,40) 33px 33px,rgb(181,53,40) 34px 34px,rgb(181,53,40) 35px 35px,rgb(182,54,41) 36px 36px,rgb(183,54,41) 37px 37px,rgb(183,54,41) 38px 38px,rgb(184,54,41) 39px 39px,rgb(184,54,41) 40px 40px,rgb(185,54,41) 41px 41px,rgb(186,55,41) 42px 42px,rgb(186,55,41) 43px 43px,rgb(187,55,42) 44px 44px,rgb(187,55,42) 45px 45px,rgb(188,55,42) 46px 46px,rgb(189,56,42) 47px 47px,rgb(189,56,42) 48px 48px,rgb(190,56,42) 49px 49px,rgb(190,56,42) 50px 50px,rgb(191,56,42) 51px 51px,rgb(192,57,43) 52px 52px;font-size:10em;left:50%;top:50%;transform:translate(-50%,-50%);-webkit-transform:translate(-50%,-50%);color:white;border:none;text-align:center;position:absolute;z-index:99999
            }

        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">@yield('code')</div>
            </div>
        </div>
    </body>
</html>
