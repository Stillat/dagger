<c-slot_forwarding.component_g #id="theComponent">
    <c-slot:footer class="one two three four">
        <?php throw new \Exception("Should Not Appear"); ?>
    </c-slot:footer>
</c-slot_forwarding.component_g>