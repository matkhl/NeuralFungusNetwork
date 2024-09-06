import { Controller } from '@hotwired/stimulus';
import {
    forceCenter,
    forceLink,
    forceManyBody,
    forceSimulation,
    select,
    drag, forceCollide
} from 'd3';

export default class extends Controller {
    static values = {
        subscriptionUrl: String
    }

    networkData = {
        neurons: [
            {hash: '40cd750bba9870f18aada2478b24840a', isOrigin: true}
        ],
        connections: []
    };

    connect() {
        this.svg = select(this.element.firstElementChild)
            .attr("width", this.element.offsetWidth)
            .attr("height", this.element.offsetHeight)
            .attr("viewBox", [0, 0, this.element.offsetWidth, this.element.offsetHeight])
            .attr("style", "max-width: 100%; height: auto;");

        this.linkGroup = this.svg.append("g").attr("class", "links");
        this.nodeGroup = this.svg.append("g").attr("class", "nodes");

        this.simulation = forceSimulation()
            .force("link", forceLink().id(d => d.hash).distance(20))
            .force("charge", forceManyBody().strength(-60))
            .force("center", forceCenter(this.element.offsetWidth / 2, this.element.offsetHeight / 2))
            .force("collide", forceCollide(10))
            .on("tick", () => this.ticked());

        this.updateData();

        const eventSource = new EventSource(this.subscriptionUrlValue.replace(/^"|"$/g, ''));
        eventSource.onmessage = event => {
            if (this.element.childElementCount) {
                this.networkData = JSON.parse(event.data);
                this.updateData();
            }
        }
    }

    updateData() {
        const nodeInfo = new Map(this.simulation.nodes().map(d => [d.hash, { x: d.x, y: d.y, vx: d.vx, vy: d.vy }]));

        // Update links
        this.links = this.linkGroup
            .selectAll("line")
            .data(this.networkData.connections, d => `${d.source.hash}-${d.target.hash}`);

        this.links.exit().remove();

        const enterLinks = this.links.enter().append("line")
            .attr("stroke", "#999")
            .attr("stroke-opacity", 0.6);

        this.links = this.links.merge(enterLinks)
            .attr("stroke-width", d => Math.sqrt(d.ttl / 10));

        // Update nodes
        this.nodes = this.nodeGroup
            .selectAll("circle")
            .data(this.networkData.neurons, d => d.hash);

        this.nodes.exit().remove();

        const enterNodes = this.nodes.enter().append("circle")
            .attr("r", 5)
            .call(this.drag(this.simulation));

        this.nodes = this.nodes.merge(enterNodes)
            .attr("fill", d => d.isOrigin ? 'gray' : 'lightblue')
            .attr("stroke", "#fff")
            .attr("stroke-width", 1.5);

        // Update simulation
        this.simulation.nodes(this.networkData.neurons);
        this.simulation.force("link").links(this.networkData.connections);

        this.simulation.nodes().forEach(node => {
            const info = nodeInfo.get(node.hash);
            if (info) {
                node.x = info.x;
                node.y = info.y;
                node.vx = info.vx;
                node.vy = info.vy;
            } else {
                node.x = this.element.offsetWidth / 2;
                node.y = this.element.offsetHeight / 2;
                node.vx = 0;
                node.vy = 0;
            }
        });

        this.simulation.alpha(0.2).restart();
    }

    ticked() {
        this.links
            .attr("x1", d => d.source.x)
            .attr("y1", d => d.source.y)
            .attr("x2", d => d.target.x)
            .attr("y2", d => d.target.y);

        this.nodes
            .attr("cx", d => d.x)
            .attr("cy", d => d.y);
    }

    drag(simulation) {
        function dragstarted(event, d) {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        }

        function dragged(event, d) {
            d.fx = event.x;
            d.fy = event.y;
        }

        function dragended(event, d) {
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        }

        return drag()
            .on("start", dragstarted)
            .on("drag", dragged)
            .on("end", dragended);
    }
}