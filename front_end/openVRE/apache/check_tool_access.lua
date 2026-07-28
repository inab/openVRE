require "apache2"

function check_access(r)
    r:err("check_tool_access: START uri=" .. r.uri)

    local tool_segment = r.uri:match("^/interactive%-tool/([^/]+)")
    if not tool_segment then
        r:err("check_tool_access: no tool_segment found, passing through")
        return 200
    end
    r:err("check_tool_access: tool_segment=" .. tool_segment)

    local project_id = tool_segment:match("__PROJ.+$")
    if not project_id then
        r:err("check_tool_access: could not parse project_id from: " .. tool_segment)
        return 403
    end
    r:err("check_tool_access: project_id=" .. project_id)

    local user = r.user or ""
    r:err("check_tool_access: oidc_user=" .. user)

    local handle = io.popen("curl -s -o /dev/null -w '%{http_code}' "
                  .. "'http://127.0.0.1/checkToolAccess.php"
                  .. "?project=" .. project_id
                  .. "&user="    .. user .. "'")
    local status = handle:read("*a")
    handle:close()

    r:err("check_tool_access: http status=" .. tostring(status))

    if status == "200" then
        return 200
    else
        r:err("check_tool_access: denied user=" .. user .. " project=" .. project_id)
        return 403
    end
end