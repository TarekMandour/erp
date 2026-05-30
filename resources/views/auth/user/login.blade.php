<html>
    <head>
        <title>user login</title>
    </head>
    <body>

        <form action="{{route('login.submit')}}" method="POST">
            @csrf

            <input type="text" name="phone" value="" />
            <input type="text" name="password" value="" />
            <input type="submit" name="submit" value="login" />
        </form>
    </body>
</html>