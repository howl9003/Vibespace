<jsp:useBean id="ACCore" class="org.archcave.beans.ACCore" scope="application" />
<jsp:useBean id="SessionBean" class="org.archcave.beans.SessionBean" scope="session" />
<HTML>
<HEAD>
    <TITLE>Register User</TITLE>
</HEAD>
<BODY>
    <%
        String status;
        if (!ACCore.isRunning())
          status = "Down";
        else
          status = "Running";
    %>
    The Server is Currently: <%= status %>

<h2>User Registration: </h2>
Number Registered: <%= ACCore.getUsersTable().length() %>
    <%
        if (!SessionBean.isActive())
        {
               String username = request.getParameter("username");
               String password = request.getParameter("password");
               if (username != null && password != null)
               {
                   // log player in via AC core bean
                   SessionBean.setUser(ACCore.getUsersTable().create());
                   SessionBean.getUser().setName(username);
                   SessionBean.getUser().setPassword(password);
                   SessionBean.activate();
                   //ACCore.getUsersTable().add(SessionBean.getUser()); // create supposedly does this =x
                   out.println("<H2>Thank you, " +
                           SessionBean.getUser().getName() +
                           ", for registering!</H2>");
               }
               else {

            // log in form
            out.println("<H2>Please login!</H2>");
            %>
              <FORM METHOD=POST ACTION="register.jsp">
               Username: <INPUT TYPE=TEXT NAME=username />
               Password: <INPUT TYPE=PASSWORD NAME=password />
               <BR /><INPUT TYPE=SUBMIT />
              </FORM>
            <%     }
        }
        else
        {
            out.println("<H2>"+ SessionBean.getUser().getName()+"is already registered.</H2>");
        }
    %>

</BODY>
</HTML>
