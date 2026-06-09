<%@ page import="org.archcave.classes.ACUsersList"%>
<jsp:useBean id="ACCore" class="org.archcave.beans.ACCore" scope="application" />

<HTML>
<HEAD>
    <TITLE>Basic Login Page</TITLE>
</HEAD>
<BODY>
    <H2>Login Here!</H2>
    <jsp:useBean id="SessionBean" class="org.archcave.beans.SessionBean" scope="session">
        <!-- Initialize Bean things -->
    </jsp:useBean>
    <%
        String status;
        if (!ACCore.isRunning())
          status = "Down";
        else
          status = "Running";
    %>
    The Server is Currently: <%= status %>
    <%
        if (!SessionBean.isActive())
        {
               String username = request.getParameter("username");
               String password = request.getParameter("password");
            
               if (username != null && password != null)
               {
                   // log player in via AC core bean
                   ACUsersList aUsersList = ACCore.getUsersTable().findByName(username);
                   if (aUsersList != null && aUsersList.length() > 0)
                   {
                       aUsersList = aUsersList.findByPassword(password);
                       if (aUsersList != null && aUsersList.length() > 0)
                       {
                           SessionBean.setUser(aUsersList.get(0));
                           SessionBean.activate();
                           out.println("<H2>You are set activated</H2>");
                       }
                       else
                       {
                           out.println("<H2>Invalid password</H2>");
                       }
                   }
                   else
                   {
                       out.println("<H2>No such username</H2>");
                   }
               }
               else {

            // log in form
            out.println("<H2>Please login!</H2>");
            %>
              <FORM METHOD=POST ACTION="login.jsp">
               Username: <INPUT TYPE=TEXT NAME=username />
               Password: <INPUT TYPE=PASSWORD NAME=password />
               <BR /><INPUT TYPE=SUBMIT />
              </FORM>
            <%     }
        }
        else
        {
            out.println("<H2>You are activated!</H2>");
        }
    %>
</BODY>
</HTML>
