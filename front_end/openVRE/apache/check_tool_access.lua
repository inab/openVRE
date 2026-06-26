require "apache2"

function check_access(r)
    r:err("check_tool_access: START uri=" .. r.uri)

    local tool_segment = r.uri:match("^/interactive%-tool/([^/]+)")
    if not tool_segment then
        r:err("check_tool_access: no tool_segment found, passing through")
        return apache2.OK
    end
    r:err("check_tool_access: tool_segment=" .. tool_segment)

    local project_id = tool_segment:match("_(.+)$")
    if not project_id then
        r:err("check_tool_access: no project_id found in " .. tool_segment)
        return apache2.HTTP_FORBIDDEN
    end
    r:err("check_tool_access: project_id=" .. project_id)

    local user = r.user
    if not user then
        r:err("check_tool_access: no user found")
        return apache2.HTTP_FORBIDDEN
    end
    r:err("check_tool_access: user=" .. user)

    local ok, http = pcall(require, "socket.http")
    if not ok then
        r:err("check_tool_access: failed to load socket.http: " .. tostring(http))
        return apache2.HTTP_FORBIDDEN
    end
    r:err("check_tool_access: socket.http loaded ok")

    local url = "http://127.0.0.1/checkToolAccess.php"
              .. "?project=" .. project_id
              .. "&user="    .. user
    r:err("check_tool_access: calling url=" .. url)

    local ok2, result, status = pcall(function()
        return http.request(url)
    end)
    if not ok2 then
        r:err("check_tool_access: http.request failed: " .. tostring(result))
        return apache2.HTTP_FORBIDDEN
    end
    r:err("check_tool_access: http status=" .. tostring(status))

    if status == 200 then
        return apache2.OK
    else
        r:err("check_tool_access: denied user=" .. user .. " project=" .. project_id)
        return apache2.HTTP_FORBIDDEN
    end
end
